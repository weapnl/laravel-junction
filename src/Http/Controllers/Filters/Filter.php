<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;

abstract class Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    abstract public static function apply(Controller $controller, Builder|Relation $query): void;
}
