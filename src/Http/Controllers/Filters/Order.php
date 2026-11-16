<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use RuntimeException;
use Weap\Junction\Http\Controllers\Controller;

class Order extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $orders = request()?->input('orders');

        if (empty($orders)) {
            return;
        }

        foreach ($orders as $order) {
            $column = $order['column'] ?? null;
            $direction = $order['direction'] ?? null;

            if ($column === null || $direction === null) {
                throw new RuntimeException('A "order" array must contain a column and a direction.');
            }

            // The order of the rows would reveal a hidden column just like a filter on it.
            static::validateColumn($query->getModel(), static::withoutModelTable($query->getModel(), (string) $column), 'orders');
            $query->orderBy($column, $direction);
        }
    }

    /**
     * A column may be qualified with the table of the model (`users.created_at`), which keeps it unambiguous when the
     * controller joins other tables. A column of any other table is returned as given, so it fails validation: there is
     * no model to check it on.
     *
     * @param Model $model
     * @param string $column
     * @return string
     */
    protected static function withoutModelTable(Model $model, string $column): string
    {
        $prefix = $model->getTable() . '.';

        return str_starts_with($column, $prefix) ? substr($column, strlen($prefix)) : $column;
    }
}
