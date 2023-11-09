<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

trait HasStore
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        if ($this->usePolicy && ! Auth::user()->can('create', $this->model)) {
            abort(403, 'Unauthorized');
        }

        if (! is_a($this->formRequest, FormRequest::class, true)) {
            throw new Exception('Property `formRequest` should inherit from `FormRequest::class`.');
        }

        $request = app($this->formRequest);

        $model = new $this->model();

        $validAttributes = $this->saveValidatedOnly || empty($model->getFillable()) ? $request->validated() : $request->only($model->getFillable());

        $model->fill(
            $this->beforeStore($validAttributes, array_diff_key($request->all(), $validAttributes))
        );

        $model->save();

        $this->storeFiles($request->allFiles(), $model);

        return response()->json(
            $this->afterStore($model)
        );
    }

    /**
     * @param array $attributes
     * @param array $invalidAttributes
     * @return array
     */
    public function beforeStore(array $attributes, array $invalidAttributes = [])
    {
        return $attributes;
    }

    /**
     * @param Model $model
     * @return Model
     */
    public function afterStore(Model $model)
    {
        return $model;
    }
}
