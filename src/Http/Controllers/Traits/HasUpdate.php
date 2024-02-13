<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

trait HasUpdate
{
    /**
     * @param int|string|Model $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id)
    {
        if ($id instanceof Model) {
            $id = $id->{$id->getKeyName()};
        }

        $model = $this->model::find($id);

        if (! $model) {
            abort(404, 'Record not found.');
        }

        if ($this->usePolicy && ! Auth::user()->can('update', $model)) {
            abort(403, 'Unauthorized');
        }

        if (! is_a($this->formRequest, FormRequest::class, true)) {
            throw new Exception('Property `formRequest` should inherit from `FormRequest::class`.');
        }

        $request = app($this->formRequest);

        $validAttributes = $this->saveFillable ? $request->only($model->getFillable()) : $request->validated();
        $invalidAttributes = array_diff_key($request->all(), $validAttributes);

        $model->fill(
            $this->beforeUpdate($model, $validAttributes, $invalidAttributes)
        );

        $model->save();

        $this->storeFiles($request->allFiles(), $model);

        return response()->json(
            $this->afterUpdate($model, $validAttributes, $invalidAttributes)
        );
    }

    /**
     * @param Model $model
     * @param array $validAttributes
     * @param array $invalidAttributes
     * @return array
     */
    public function beforeUpdate(Model $model, array $validAttributes, array $invalidAttributes)
    {
        return $validAttributes;
    }

    /**
     * @param Model $model
     * @param array $validAttributes
     * @param array $invalidAttributes
     * @return Model
     */
    public function afterUpdate(Model $model, array $validAttributes, array $invalidAttributes)
    {
        return $model;
    }
}
