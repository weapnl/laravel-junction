<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Validators\Scopes as ScopesValidator;

class Scopes extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $scopes = request()?->input('scopes');

        if (! $scopes) {
            return;
        }

        $scopes = ScopesValidator::validate($controller, $scopes);

        foreach ($scopes as $scope) {
            $scopeName = $scope['name'];

            $query->$scopeName(...$scope['params']);
        }
    }
}
