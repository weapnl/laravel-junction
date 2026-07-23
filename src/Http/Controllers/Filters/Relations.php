<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionMethod;
use Weap\Junction\AttributeRelationCache;
use Weap\Junction\Extensions\RelationExtension;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Validators\Relations as RelationsValidator;

class Relations extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $relations = request()->getRelations();

        RelationsValidator::validate($controller, $relations ?: []);

        $relations = collect($relations)->flip()->undot();

        $accessorRelations = static::getAccessorRelations(
            $query->getModel()::class,
            collect(request()->getAccessors())->flip()->undot()->all()
        );

        $relationFilters = collect(app(RelationExtension::class)->call($controller->relations(), $controller))
            ->mapWithKeys(fn ($closure, $relation) => [$relation => is_callable($closure) ? $closure : null])
            ->filter()
            ->all();

        $relations
            ->mergeRecursive($accessorRelations)
            ->each(function ($nestedRelations, $relation) use ($query, $relationFilters) {
                static::addWith($query, $relation, $nestedRelations, $relationFilters);
            });
    }

    /**
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
     * @param string $relation
     * @param array<int|string, mixed>|Closure|int $nestedRelations
     * @param array<string, callable|null> $relationFilters
     * @return void
     */
    protected static function addWith(Builder|Relation $query, string $relation, array|Closure|int $nestedRelations, array $relationFilters): void
    {
        $relationFilters = array_filter(Arr::mapWithKeys($relationFilters, fn ($closure, $filterRelation): array => Str::startsWith($filterRelation, $relation) ? [Str::after($filterRelation, $relation) => $closure] : [$filterRelation => null]));

        $query->with($relation, function (Builder|Relation $query) use ($nestedRelations, $relationFilters) {
            $nestedRelations = is_array($nestedRelations) ? $nestedRelations : [$nestedRelations];

            $currentRelationFilters = Arr::where($relationFilters, fn ($closure, $filterRelation): bool => ! Str::startsWith($filterRelation, '.'));

            foreach ($currentRelationFilters as $currentRelationFilter) {
                $currentRelationFilter($query);
            }

            $remainingRelationFilters = Arr::mapWithKeys($relationFilters, fn ($closure, $filterRelation): array => Str::startsWith($filterRelation, '.') ? [Str::after($filterRelation, '.') => $closure] : [$filterRelation => null]);

            foreach ($nestedRelations as $nestedRelation => $nestedRelations) {
                if (is_string($nestedRelation)) {
                    /** @var Builder<Model>|Relation<Model, Model, mixed> $query */
                    static::addWith($query, $nestedRelation, $nestedRelations, $remainingRelationFilters);
                } elseif (is_callable($nestedRelations)) {
                    $nestedRelations($query);
                }
            }
        });
    }

    /**
     * @param class-string $modelClass
     * @param array<string, mixed> $accessors
     * @return array<string, mixed>
     */
    protected static function getAccessorRelations(string $modelClass, array $accessors): array
    {
        $relations = [];

        foreach ($accessors as $accessor => $nestedAccessors) {
            $accessor = Str::camel($accessor);

            if (! method_exists($modelClass, $accessor)) {
                continue;
            }

            // If the accessor is declared as a public method, we can not call it statically
            $attribute = ((new ReflectionMethod($modelClass, $accessor))->isPublic())
                ? (new $modelClass())->$accessor()
                : $modelClass::$accessor();

            if ($attribute instanceof Relation) {
                $relations[$accessor] ??= [];
                $relations[$accessor] += static::getAccessorRelations($attribute->getModel()::class, $nestedAccessors);

                continue;
            }

            if ($attribute instanceof Attribute && ($with = app(AttributeRelationCache::class)->get($modelClass, $accessor))) {
                foreach ($with as $key => $relation) {
                    $relationKey = is_callable($relation) ? $key : $relation;
                    $relationValue = is_callable($relation) ? [$relation] : [];

                    $relations[$relationKey] = [
                        ...($relations[$relationKey] ?? []),
                        ...$relationValue,
                    ];
                }
            }
        }

        return $relations;
    }
}
