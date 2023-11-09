<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
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
        $relations = request()?->input('with');

        if (! $relations) {
            return;
        }

        RelationsValidator::validate($controller, $relations);

        collect($relations)->each(function ($relation) use ($query, $controller) {
            static::addWith($query, $controller, $relation);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param Controller $controller
     * @param string $relation
     * @return void
     */
    protected static function addWith(Builder|Relation $query, Controller $controller, string $relation): void
    {
        $query->with($relation, $controller->relations()[$relation] ?? function(){});

        $splitRelation = Str::of($relation)->explode('.');

        $splitRelation->pop();

        if ($splitRelation->isEmpty()) {
            return;
        }

        static::addWith($query, $controller, $splitRelation->join('.'));
    }
}
