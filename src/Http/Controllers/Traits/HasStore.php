<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Weap\Junction\Http\Controllers\Helpers\Database;

trait HasStore
{
    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws Throwable
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

        $model = Database::storeInTransactionIfEnabled(function () use ($request) {
            $model = new $this->model();

            $validAttributes = $this->saveFillable ? $request->only($model->getFillable()) : $request->validated();
            $invalidAttributes = array_diff_key($request->all(), $validAttributes);

            $model->fill(
                $this->beforeStore($validAttributes, $invalidAttributes)
            );

            $model->save();
            $this->attachMedia($model, $validAttributes);

            return $this->afterStore($model, $validAttributes, $invalidAttributes);
        });

        return response()->json($model);
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
