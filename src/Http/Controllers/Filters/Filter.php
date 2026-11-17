<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\RelationFinder;
use Weap\Junction\Http\Controllers\Resources\BaseResource;

abstract class Filter
{
    /**
     * @param Controller $controller
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
     */
    abstract public static function apply(Controller $controller, Builder|Relation $query): void;

    /**
     * Eloquent calls the method of every segment of a relation path, so a path from the request must only contain
     * declared relations.
     *
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
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

    /**
     * A filter compares a column with a value the client chooses, and the rows it returns tell whether the guess was
     * right, so a hidden column could be read one guess at a time. The column must also be a plain name: Laravel reads
     * `->` as a key inside a JSON column, which `$hidden` does not cover.
     *
     * @param Model $model The model whose table holds the column.
     * @param string $column
     * @param string $parameter The request parameter the column comes from, used as the key of the validation error.
     * @return void
     *
     * @throws ValidationException
     */
    protected static function validateColumn(Model $model, string $column, string $parameter): void
    {
        if (! static::isColumnName($column) || static::isHiddenColumn($model, $column)) {
            throw ValidationException::withMessages([
                $parameter => "Invalid column: {$column}",
            ]);
        }
    }

    /**
     * The primary key is often left out of `$visible` while clients still filter on it, so for the key only `$hidden`
     * counts: a model that hides its key, such as a session or token table, keeps it secret.
     *
     * @param Model $model
     * @param string $column
     * @return bool
     */
    protected static function isHiddenColumn(Model $model, string $column): bool
    {
        if ($column === $model->getKeyName()) {
            return in_array($column, $model->getHidden(), true);
        }

        return BaseResource::isHiddenOn($model, $column);
    }

    /**
     * Unlike an accessor name, a column may start with a digit (`2fa_enabled`): Laravel quotes the column in the query.
     *
     * @param string $column
     * @return bool
     */
    protected static function isColumnName(string $column): bool
    {
        // `\z`, because `$` also matches before a trailing newline.
        return preg_match('/^[A-Za-z0-9_]+\z/', $column) === 1;
    }

    /**
     * The model whose table holds a column filtered through the given relation: a `belongsToMany` filters on the columns
     * of its pivot table.
     *
     * @param Relation<Model, Model, mixed> $relation
     * @param Builder<Model> $relatedQuery The query on the related model, inside `whereHas`.
     * @return Model
     */
    protected static function columnModel(Relation $relation, Builder $relatedQuery): Model
    {
        return $relation instanceof BelongsToMany ? $relation->newPivot() : $relatedQuery->getModel();
    }
}
