<?php

namespace Weap\Junction\Http\Controllers\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
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
use Weap\Junction\Http\Controllers\Response\Item;
use Weap\Junction\Http\Resources\BaseResource;

trait HasShow
{
    /**
     * @param int|string|Model $id
     * @return JsonResource
     *
     * @throws Exception
     */
    public function show(int|string|Model $id): JsonResource
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

        $resource = new $this->resource($item->getModel());

        if ($resource instanceof BaseResource) {
            $resource::withoutWrapping();

            $pluckFields = request()->getPluckFields();
            $accessors = request()->getAccessors();
            $relations = request()->getRelations();

            $resource = $resource->pluckFields(
                pluckAttributes: $pluckFields !== null ? Arr::undot(array_flip($pluckFields)) : null,
                pluckAccessors: $accessors !== null ? Arr::undot(array_flip($accessors)) : null,
                pluckRelations: $relations !== null ? Arr::undot(array_flip($relations)) : null,
            );
        }

        return $resource;
    }

    /**
     * @param Builder<Model> $query
     */
    public function beforeShow(Builder $query): void
    {
        //
    }

    /**
     * @param Item $item
     */
    public function afterShow(Item $item): void
    {
        //
    }
}
