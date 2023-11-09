<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\Table;

class WhereIn extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     * @throws Exception
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $whereIns = request()?->input('where_in');

        if (! $whereIns) {
            return;
        }

        $whereIns = (array) $whereIns;

        foreach ($whereIns as $whereIn) {
            $values = (array) ($whereIn['values'] ?? []);

            self::traverse($query, $whereIn['column'], $values);
        }
    }

    /**
     * @param Builder $query
     * @param string $column
     * @param array $values
     */
    protected static function traverse(Builder $query, string $column, array $values): void
    {
        $relationParts = explode('.', $column);

        // Directly on the main model (no relation)
        if (count($relationParts) === 1) {
            $query->whereIn($query->getModel()->getTable() . '.' . $column, $values);
            return;
        }

        // Treatment for columns in a relationship
        $actualColumn = array_pop($relationParts);
        $relationPath = implode('.', $relationParts);
        $tableName = Table::getRelationTableName($query->getModel()::class, $relationParts);

        $query->whereHas($relationPath, function ($subQuery) use ($actualColumn, $values, $tableName) {
            $fullColumn = $tableName . '.' . $actualColumn;
            $subQuery->whereIn($fullColumn, $values);
        });
    }
}
