<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\Table;

class Wheres extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     * @throws Exception
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $wheres = request()?->input('wheres');

        if (! $wheres) {
            return;
        }

        foreach ($wheres as $where) {
            $column = $where['column'] ?? null;
            $operator = $where['operator'] ?? null;
            $value = $where['value'] ?? null;

            if ($column === null || $operator === null) {
                throw new \RuntimeException('A "where" string must contain a column and a operator.');
            }

            self::traverse($query, $column, $operator, $value);
        }
    }

    /**
     * @param Builder|Relation $query
     * @param string $column
     * @param string $operator
     * @param string|null $value
     */
    protected static function traverse(Builder|Relation $query, string $column, string $operator, ?string $value): void
    {
        $columnParts = explode('.', $column);

        // If there's no relation (single column name), apply the where condition and exit early.
        if (count($columnParts) === 1) {
            static::applyWhere($query, $query->getModel()->getTable() . '.' . $column, $operator, $value);

            return;
        }

        $actualColumn = array_pop($columnParts);
        $relationPath = implode('.', $columnParts);
        $tableName = Table::getRelationTableName($query->getModel()::class, $columnParts);

        $query->whereHas($relationPath, function ($innerQuery) use ($actualColumn, $operator, $value, $tableName) {
            $fullColumn = $tableName ? $tableName . '.' . $actualColumn : $actualColumn;

            static::applyWhere($innerQuery, $fullColumn, $operator, $value);
        });
    }

    /**
     * @param $query
     * @param string $column
     * @param string $operator
     * @param string|null $value
     * @return void
     */
    protected static function applyWhere($query, string $column, string $operator, ?string $value): void
    {
        if (empty($value) || $value === 'null') {
            if (in_array($operator, ['!=', 'IS NOT'], true)) {
                $query->whereNotNull($column);
            } else {
                $query->whereNull($column);
            }

            return;
        }

        $query->where($column, $operator, $value);
    }
}
