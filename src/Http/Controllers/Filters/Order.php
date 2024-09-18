<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use RuntimeException;
use Weap\Junction\Http\Controllers\Controller;
use Illuminate\Support\Collection;

class Order extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $orders = request()?->collect('orders');

        if (config('junction.route.index.always_order_on_primary_key', false)) {
            $orders = static::orderOnPrimaryKey($controller->model, $orders);
        }
        

        $orders->each(function (array $order) use ($query) {
            $column = $order['column'];
            $direction = $order['direction'];

            if (is_null($column) || is_null($direction)) {
                throw new RuntimeException('A "order" array must contain a column and a direction.');
            }

            $query->orderBy($column, $direction);
        });
    }

    protected static function orderOnPrimaryKey(string $model, Collection $orders): Collection
    {
        $keyName = (new $model())->getKeyName();
        $qualifiedKeyName = (new $model())->getQualifiedKeyName();

        if (!$orders->contains(function ($order) use ($keyName, $qualifiedKeyName) {
            return $order['column'] && in_array($order['column'], [$keyName, $qualifiedKeyName]);
        })) {
            $orders->push(['column' => $qualifiedKeyName, 'direction' => 'asc']);
        }

        return $orders;
    }
}
