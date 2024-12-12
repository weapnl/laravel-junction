<?php

namespace Weap\Junction\Http\Controllers\Filters\Index;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Filters\Filter;

class EnforceOrderByModelKey extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        $enforceOrderByModelKey = config('junction.route.index.enforce_order_by_model_key', false);

        if (! $enforceOrderByModelKey) {
            return;
        }

        $direction = 'asc';

        if (is_string($enforceOrderByModelKey) && in_array($enforceOrderByModelKey, ['asc', 'desc'])) {
            $direction = $enforceOrderByModelKey;
        }

        $baseQuery = $query->getQuery();
        $combinedQueryOrders = [
            ...($baseQuery->orders ?? []),
            ...($baseQuery->unionOrders ?? []),
        ];

        $model = $query->getModel();
        $modelKeyNames = [
            $model->getKeyName(),
            $model->getQualifiedKeyName(),
        ];

        if (collect($combinedQueryOrders)->whereIn('column', $modelKeyNames)->isEmpty()) {
            $query->orderBy($model->getQualifiedKeyName(), $direction);
        }
    }
}
