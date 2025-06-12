<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

        if (! $relations) {
            return;
        }

        RelationsValidator::validate($controller, $relations);

        $relationFilters = collect($controller->relations())
            ->filter(fn ($closure) => is_callable($closure))
            ->undot();

        $accessors = collect(request()?->getAccessors())
            ->flip()
            ->undot();

        collect($relations)
            ->flip()
            ->undot()
            ->each(function ($nestedRelations, $relation) use ($query, $relationFilters, $accessors) {
                static::addWith(
                    $query,
                    $relation,
                    is_array($nestedRelations) || is_callable($nestedRelations) ? $nestedRelations : [],
                    $relationFilters->all(),
                    $accessors->all(),
                );
            });
    }

    /**
     * @param Builder|Relation $query
     * @param string $relation
     * @param array|Closure $nestedRelations
     * @param array $relationFilters
     * @param array $accessors
     * @return void
     */
    protected static function addWith(Builder|Relation $query, string $relation, array|Closure $nestedRelations, array $relationFilters, array $accessors): void
    {
        $query->with($relation, function (Builder|Relation $query) use ($relation, $relationFilters, $nestedRelations, $accessors) {
            if (is_callable($nestedRelations)) {
                $nestedRelations($query);
            }

            $relationFilter = $relationFilters[$relation] ?? [];

            if (is_callable($relationFilter)) {
                $relationFilter($query);
            }

            $accessors = $accessors[$relation] ?? [];

            foreach ($accessors as $accessor => $nestedAccessors) {
                if (is_array($nestedAccessors)) {
                    continue;
                }

                $accessor = Str::camel($accessor);
                $attribute = method_exists($query->getModel(), $accessor) ? $query->getModel()::$accessor() : null;

                if ($attribute instanceof Attribute && ($with = Junction::$cachedAttributeRelations[$query->getModel()::class][$accessor] ?? null)) {
                    $nestedRelations += Arr::mapWithKeys($with, fn ($relation, $key) => is_callable($relation) ? [$key => $relation] : [$relation => $key]);
                }
            }

            foreach ($nestedRelations as $nestedRelation => $nestedRelations) {
                static::addWith(
                    $query,
                    $nestedRelation,
                    is_array($nestedRelations) || is_callable($nestedRelations) ? $nestedRelations : [],
                    is_array($relationFilter) ? $relationFilter : [],
                    is_array($accessors) ? $accessors : [],
                );
            }
        });
    }
}
