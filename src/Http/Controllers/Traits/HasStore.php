<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

trait HasStore
{
    /**
     * @return JsonResponse
     * @throws Exception
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
     * @param array $attributes
     * @param array $invalidAttributes
     * @return array
     */
    public function beforeStore(array $attributes, array $invalidAttributes): array
    {
        return $attributes;
    }

    /**
     * @param Model $model
     * @param array $attributes
     * @param array $invalidAttributes
     * @return Model
     */
    public function afterStore(Model $model, array $attributes, array $invalidAttributes): Model
    {
        return $model;
    }
}
