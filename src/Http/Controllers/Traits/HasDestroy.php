<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasDestroy
{
    /**
     * @param int|string|Model $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
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

        $this->beforeDestroy($model);

        $model->delete();

        return response()->json(
            $this->afterDestroy($model)
        );
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
