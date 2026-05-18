<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use RuntimeException;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\Table;

class Order extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     *
     * @throws Exception
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $orders = request()?->input('orders');

        if (! $orders) {
            return;
        }

        foreach ($orders as $order) {
            $column = $order['column'] ?? null;
            $direction = $order['direction'] ?? null;

            if ($column === null || $direction === null) {
                throw new RuntimeException('A "order" array must contain a column and a direction.');
            }

            self::traverse($query, $column, $direction);
        }
    }

    /**
     * @param Builder|Relation $query
     * @param string $column
     * @param string $direction
     */
    protected static function traverse(Builder|Relation $query, string $column, string $direction): void
    {
        $columnParts = explode('.', $column);

        // If there's no relation (single column name), apply the order and exit early.
        if (count($columnParts) === 1) {
            static::applyOrder($query, $query->getModel()->getTable() . '.' . $column, $direction);

            return;
        }

        $actualColumn = array_pop($columnParts);
        $relationPath = implode('.', $columnParts);
        $relation = Table::getRelation($query->getModel()::class, $columnParts);

        $relatedModel = $relation->getRelated();
        $tableName = $relation instanceof BelongsToMany ? $relation->getTable() : $relatedModel->getTable();

        // Create unique alias
        $alias = 'order_' . str_replace('.', '_', $relationPath);

        // Add the join
        $query->leftJoin($tableName . ' as ' . $alias, function($join) use ($query, $relation, $alias) {
            if (method_exists($relation, 'getForeignKeyName') && method_exists($relation, 'getOwnerKeyName')) {
                $join->on($query->getModel()->getTable() . '.' . $relation->getForeignKeyName(), '=', $alias . '.' . $relation->getOwnerKeyName());
            } else {
                // Fallback
                $join->on($query->getModel()->getTable() . '.id', '=', $alias . '.id');
            }
        });

        static::applyOrder($query, $alias . '.' . $actualColumn, $direction);
    }

    /**
     * @param Builder|Relation $query
     * @param string $column
     * @param string $direction
     * @return void
     */
    protected static function applyOrder($query, string $column, string $direction): void
    {
        $query->orderBy($column, $direction);
    }
}
