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

trait HasUpdate
{
    /**
     * @param int|string|Model $id
     * @return JsonResource
     *
     * @throws Throwable
     */
    public function update(int|string|Model $id): JsonResource
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

        $model = Database::updateInTransactionIfEnabled(function () use ($model, $request) {
            $validAttributes = $this->saveFillable ? $request->only($model->getFillable()) : $request->validated();
            $invalidAttributes = array_diff_key($request->all(), $validAttributes);

            $model->fill(
                $this->beforeUpdate($model, $validAttributes, $invalidAttributes)
            );

            $model->save();

            $this->attachMedia($model, $validAttributes);

            return $this->afterUpdate($model, $validAttributes, $invalidAttributes);
        });

        $resource = new $this->resource($model);

        if ($resource instanceof BaseResource) {
            $resource::withoutWrapping();
        }

        return $resource;
    }

    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     * @return array<string, mixed>
     */
    public function beforeUpdate(Model $model, array $validAttributes, array $invalidAttributes): array
    {
        return $validAttributes;
    }

    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     * @return Model
     */
    public function afterUpdate(Model $model, array $validAttributes, array $invalidAttributes): Model
    {
        return $model;
    }
}
