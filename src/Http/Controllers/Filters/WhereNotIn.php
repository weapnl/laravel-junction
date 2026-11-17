<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Support\Table;

class WhereNotIn extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
     *
     * @throws Exception
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $whereNotIns = request()->input('where_not_in');

        if (! $whereNotIns) {
            return;
        }

        $whereNotIns = (array) $whereNotIns;

        foreach ($whereNotIns as $whereNotIn) {
            $values = (array) ($whereNotIn['values'] ?? []);

            self::traverse($query, $whereNotIn['column'], $values);
        }
    }

    /**
     * @param Builder<Model> $query
     * @param string $column
     * @param array<int|string, mixed> $values
     */
    protected static function traverse(Builder $query, string $column, array $values): void
    {
        $relationParts = explode('.', $column);

        // Directly on the main model (no relation)
        if (count($relationParts) === 1) {
            static::validateColumn($query->getModel(), $column, 'where_not_in');
            $query->whereNotIn($query->getModel()->getTable() . '.' . $column, $values);

            return;
        }

        // Treatment for columns in a relationship
        $actualColumn = array_pop($relationParts);
        $relationPath = implode('.', $relationParts);
        static::validateRelationPath($query, $relationPath, 'where_not_in');
        $relation = Table::getRelation($query->getModel()::class, $relationParts);

        // Checked inside the closure, because behind a `morphTo` it runs once for each related model type.
        $query->whereHas($relationPath, function (Builder $subQuery) use ($actualColumn, $values, $relation) {
            static::validateColumn(static::columnModel($relation, $subQuery), $actualColumn, 'where_not_in');

            $tableName = $relation instanceof BelongsToMany ? $relation->getTable() : $subQuery->getModel()->getTable();
            $fullColumn = $tableName . '.' . $actualColumn;

            $subQuery->whereNotIn($fullColumn, $values);
        });
    }
}
