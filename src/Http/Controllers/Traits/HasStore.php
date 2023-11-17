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

        $validAttributes = empty($request->rules()) ? $request->only($model->getFillable()) : $request->validated();
        $invalidAttributes = array_diff_key($request->all(), $validAttributes);

        $model->fill(
            $this->beforeStore($validAttributes, $invalidAttributes)
        );

        $model->save();
        $this->storeFiles($request->allFiles(), $model);

        return response()->json(
            $this->afterStore($model, $validAttributes, $invalidAttributes)
        );
    }

    /**
     * @param array $validAttributes
     * @param array $invalidAttributes
     * @return array
     */
    public function beforeStore(array $validAttributes, array $invalidAttributes)
    {
        return $validAttributes;
    }

    /**
     * @param Model $model
     * @param array $validAttributes
     * @param array $invalidAttributes
     * @return Model
     */
    public function afterStore(Model $model, array $validAttributes, array $invalidAttributes)
    {
        return $model;
    }
}
