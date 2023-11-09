<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Validators\Relations as RelationsValidator;

class Count extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $relations = request()?->input('count');

        if (! $relations) {
            return;
        }

        $relations = collect($relations)->mapWithKeys(function ($relation) {
            $split = explode('|', $relation);

            return [
                $split[0] => function ($query) use ($split) {
                    return isset($split[1]) ? $query->where('id', $split[1]) : $query;
                }
            ];
        });

        $query->withCount(
            RelationsValidator::validate($controller, $relations->all())
        );
    }
}
