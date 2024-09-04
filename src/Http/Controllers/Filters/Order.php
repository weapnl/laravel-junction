<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
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

            $query->orderBy($column, $direction);
        }
    }
}
