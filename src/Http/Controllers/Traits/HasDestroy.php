<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Weap\Junction\Http\Controllers\Helpers\TransactionHelper;

trait HasDestroy
{
    /**
     * @param int|string|Model $id
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function destroy($id)
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

        $model = TransactionHelper::runInTransactionIfEnabled(function () use ($model) {
            $this->beforeDestroy($model);

            $model->delete();

            return $this->afterDestroy($model);
        });

        return response()->json($model);
    }

    /**
     * @param Model $model
     */
    public function beforeDestroy(Model $model)
    {
        //
    }

    /**
     * @param Model $model
     * @return Model
     */
    public function afterDestroy(Model $model)
    {
        return $model;
    }
}
