<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Weap\Junction\Http\Controllers\Filters\Count;
use Weap\Junction\Http\Controllers\Filters\Relations;
use Weap\Junction\Http\Controllers\Filters\Scopes;
use Weap\Junction\Http\Controllers\Filters\WhereIn;
use Weap\Junction\Http\Controllers\Filters\WhereNotIn;
use Weap\Junction\Http\Controllers\Filters\Wheres;
use Weap\Junction\Http\Controllers\Modifiers\Appends;
use Weap\Junction\Http\Controllers\Modifiers\HiddenFields;
use Weap\Junction\Http\Controllers\Resources\BaseResource;
use Weap\Junction\Http\Controllers\Response\Item;

trait HasShow
{
    /**
     * @param int|string $id
     * @return BaseResource
     * @throws Exception
     */
    public function show($id)
    {
        if ($id instanceof Model) {
            $id = $id->{$id->getKeyName()};
        }

        $query = $this->model::query();

        $this->beforeShow($query);

        Relations::apply($this, $query);
        Scopes::apply($this, $query);
        Wheres::apply($this, $query);
        WhereIn::apply($this, $query);
        WhereNotIn::apply($this, $query);
        Count::apply($this, $query);

        $model = $query->find($id);

        if (! $model) {
            abort(404, 'Record not found.');
        }

        if ($this->usePolicy && ! Auth::user()->can('view', $model)) {
            abort(403, 'Unauthorized');
        }

        $item = Item::model($model);

        HiddenFields::apply($this, $item);
        Appends::apply($this, $item);

        $this->afterShow($item);

        $pluckFields = request()?->input('pluck');
        $accessors = request()?->input('appends');
        $relations = request()?->input('with');

        $this->resource::withoutWrapping();

        return (new $this->resource($item->getModel()))->pluckFields(
            pluckAttributes: $pluckFields !== null ? Arr::undot(array_flip($pluckFields)) : null,
            pluckAccessors: $accessors !== null ? Arr::undot(array_flip($accessors)) : null,
            pluckRelations: $relations !== null ? Arr::undot(array_flip($relations)) : null,
        );
    }

    /**
     * @param Builder $query
     */
    public function beforeShow(Builder &$query)
    {
        //
    }

    /**
     * @param Item $item
     */
    public function afterShow(Item &$item)
    {
        //
    }
}
