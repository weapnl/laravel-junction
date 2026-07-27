<?php

namespace Weap\Junction\Http\Controllers\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Weap\Junction\Http\Resources\BaseResource;
use Weap\Junction\Support\Database;

trait HasStore
{
    /**
     * @return JsonResource
     *
     * @throws Throwable
     */
    public function store(): JsonResource
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

        $resource = new $this->resource($model);

        if ($resource instanceof BaseResource) {
            $resource::withoutWrapping();
        }

        return $resource;
    }

    /**
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     * @return array<string, mixed>
     */
    public function beforeStore(array $validAttributes, array $invalidAttributes): array
    {
        return $validAttributes;
    }

    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     * @return Model
     */
    public function afterStore(Model $model, array $validAttributes, array $invalidAttributes): Model
    {
        return $model;
    }
}
