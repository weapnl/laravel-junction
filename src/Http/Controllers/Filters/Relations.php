<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionMethod;
use Weap\Junction\AttributeRelationCache;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Validators\Relations as RelationsValidator;

class Relations extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $relations = request()?->getRelations();

        RelationsValidator::validate($controller, $relations ?: []);

        $relations = collect($relations)->flip()->undot();

        $accessorRelations = static::getAccessorRelations(
            $query->getModel()::class,
            collect(request()?->getAccessors())->flip()->undot()->all()
        );

        $relationFilters = collect($controller->relations())
            ->filter(fn ($closure) => is_callable($closure))
            ->undot();

        $relations
            ->mergeRecursive($accessorRelations)
            ->each(function ($nestedRelations, $relation) use ($query, $relationFilters) {
                static::addWith($query, $relation, $nestedRelations, $relationFilters[$relation] ?? []);
            });
    }

    /**
     * @param Builder|Relation $query
     * @param string $relation
     * @param array|Closure|int $nestedRelations
     * @param array $relationFilters
     * @return void
     */
    protected static function addWith(Builder|Relation $query, string $relation, array|Closure|int $nestedRelations, array|Closure|null $relationFilters): void
    {
        $query->with($relation, function (Builder|Relation $query) use ($nestedRelations, $relation, $relationFilters) {
            $nestedRelations = is_array($nestedRelations) ? $nestedRelations : [$nestedRelations];

            if (is_callable($relationFilters)) {
                $relationFilters($query);
            }

            foreach (is_array($nestedRelations) ? $nestedRelations : [] as $nestedRelation => $nestedRelations) {
                if (is_string($nestedRelation)) {
                    static::addWith($query, $nestedRelation, $nestedRelations, is_array($relationFilters) ? $relationFilters[$nestedRelation] ?? null : null);
                } elseif (is_callable($nestedRelations)) {
                    $nestedRelations($query);
                }
            }
        });
    }

    /**
     * @param class-string $modelClass
     * @param array $accessors
     * @return array
     */
    protected static function getAccessorRelations(string $modelClass, array $accessors)
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
