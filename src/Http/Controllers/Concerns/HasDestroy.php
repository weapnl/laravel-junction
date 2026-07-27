<?php

namespace Weap\Junction\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Weap\Junction\Http\Resources\BaseResource;
use Weap\Junction\Support\Database;

trait HasDestroy
{
    /**
     * @param int|string|Model $id
     * @return JsonResource
     *
     * @throws Throwable
     */
    public function destroy(int|string|Model $id): JsonResource
    {
        if ($id instanceof Model) {
            $id = $id->{$id->getKeyName()};
        }

        $model = $this->model::find($id);

        if (! $model) {
            abort(404, 'Record not found.');
        }

        if ($this->usePolicy && ! Auth::user()->can('delete', $model)) {
            abort(403, 'Unauthorized');
        }

        $model = Database::destroyInTransactionIfEnabled(function () use ($model) {
            $this->beforeDestroy($model);

            $model->delete();

            return $this->afterDestroy($model);
        });

        $resource = new $this->resource($model);

        if ($resource instanceof BaseResource) {
            $resource::withoutWrapping();
        }

        return $resource;
    }

    /**
     * @param Model $model
     */
    public function beforeDestroy(Model $model): void
    {
        //
    }

    /**
     * @param Model $model
     * @return Model
     */
    public function afterDestroy(Model $model): Model
    {
        return $model;
    }
}
