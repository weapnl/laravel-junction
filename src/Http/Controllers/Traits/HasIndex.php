<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Weap\Junction\Http\Controllers\Filters\Count;
use Weap\Junction\Http\Controllers\Filters\Limit;
use Weap\Junction\Http\Controllers\Filters\Order;
use Weap\Junction\Http\Controllers\Filters\Relations;
use Weap\Junction\Http\Controllers\Filters\Scopes;
use Weap\Junction\Http\Controllers\Filters\Search;
use Weap\Junction\Http\Controllers\Filters\WhereIn;
use Weap\Junction\Http\Controllers\Filters\WhereNotIn;
use Weap\Junction\Http\Controllers\Filters\Wheres;
use Weap\Junction\Http\Controllers\Modifiers\Appends;
use Weap\Junction\Http\Controllers\Modifiers\HiddenFields;
use Weap\Junction\Http\Controllers\Response\Items;

trait HasIndex
{
    /**
     * @return AnonymousResourceCollection
     *
     * @throws Throwable
     */
    public function index()
    {
        if ($this->usePolicy && ! Auth::user()->can('viewAny', $this->model)) {
            abort(403, 'Unauthorized');
        }

        $simplePagination = request()->boolean('simple_pagination');

        if ($this->forceSimplePagination === true && ! $simplePagination) {
            abort(400, 'Simple pagination is required for this resource.');
        }

        /** @var Builder $query */
        $query = $this->model::query();

        $this->beforeIndex($query);

        Relations::apply($this, $query);
        Scopes::apply($this, $query);
        Search::apply($this, $query);
        Wheres::apply($this, $query);
        WhereIn::apply($this, $query);
        WhereNotIn::apply($this, $query);
        Limit::apply($this, $query);
        Order::apply($this, $query);
        Count::apply($this, $query);

        $items = Items::query($query)
            ->simplePagination($simplePagination)
            ->enforceOrderByModelKey(
                (bool) config('junction.route.index.enforce_order_by_model_key', false),
                config('junction.route.index.enforce_order_by_model_key_direction'),
            )
            ->get();

        HiddenFields::apply($this, $items);
        Appends::apply($this, $items);

        $this->afterIndex($items);

        $pluckFields = request()?->input('pluck');
        $accessors = request()?->input('appends');
        $relations = request()?->input('with');

        return $this->resource::items(
            $items,
            pluckAttributes: $pluckFields !== null ? Arr::undot(array_flip($pluckFields)) : null,
            pluckAccessors: $accessors !== null ? Arr::undot(array_flip($accessors)) : null,
            pluckRelations: $relations !== null ? Arr::undot(array_flip($relations)) : null,
        );
    }

    /**
     * @param Builder $query
     */
    public function beforeIndex(Builder &$query)
    {
        //
    }

    /**
     * @param Items $items
     */
    public function afterIndex(Items &$items)
    {
        //
    }
}
