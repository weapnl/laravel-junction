<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionMethod;
use Weap\Junction\AttributeRelationCache;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Validators\Relations as RelationsValidator;
use Weap\Junction\Junction;

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

        $relationFilters = collect($controller->relations())
            ->filter(fn ($closure) => is_callable($closure))
            ->undot();

        $accessorRelations = static::getAccessorRelations(
            $query->getModel()::class,
            collect(request()?->getAccessors())->flip()->undot()->all()
        );

        $relations
            ->mergeRecursive($relationFilters)
            ->mergeRecursive($accessorRelations)
            ->each(function ($nestedRelations, $relation) use ($query) {
                static::addWith($query, $relation, $nestedRelations);
            });
    }

    /**
     * @param Builder|Relation $query
     * @param string $relation
     * @param array|Closure|int $nestedRelations
     * @return void
     */
    protected static function addWith(Builder|Relation $query, string $relation, array|Closure|int $nestedRelations): void
    {
        $query->with($relation, function (Builder|Relation $query) use ($nestedRelations, $relation) {
            $nestedRelations = is_array($nestedRelations) ? $nestedRelations : [$nestedRelations];

            foreach (is_array($nestedRelations) ? $nestedRelations : [] as $nestedRelation => $nestedRelations) {
                if (is_string($nestedRelation)) {
                    static::addWith($query, $nestedRelation, $nestedRelations);
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

            $cache = app(AttributeRelationCache::class);

            if ($attribute instanceof Attribute && ($with = $cache->get($modelClass, $accessor))) {
                $relations += Arr::mapWithKeys($with, fn ($relation, $key) => is_callable($relation) ? [$key => $relation] : [$relation => $key]);
            }
        }

        return $relations;
    }
}
