<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\RelationFinder;

abstract class Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    abstract public static function apply(Controller $controller, Builder|Relation $query): void;

    /**
     * Eloquent calls the method of every segment of a relation path, so a path from the request must only contain
     * declared relations.
     *
     * @param Builder|Relation $query
     * @param string $relationPath
     * @param string $parameter The request parameter the path comes from, used as the key of the validation error.
     * @return void
     *
     * @throws ValidationException
     */
    protected static function validateRelationPath(Builder|Relation $query, string $relationPath, string $parameter): void
    {
        if (! RelationFinder::isRelationPath($query->getModel(), $relationPath)) {
            throw ValidationException::withMessages([
                $parameter => "Invalid relation: {$relationPath}",
            ]);
        }
    }
}
