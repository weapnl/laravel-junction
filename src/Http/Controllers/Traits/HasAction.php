<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait HasAction
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function action()
    {
        request()->validate([
            'action' => [
                'required',
                Rule::in($this->getActions()),
            ]
        ]);

        $model = null;

        if (request()->id) {
            $model = $this->model::find(request()->id);

            if (! $model) {
                abort(404, 'Record not found.');
            }
        }

        if ($this->usePolicy && ! Auth::user()->can('action', $model ?: $this->model)) {
            abort(403, 'Unauthorized');
        }

        return $this->{$this->getActionMethod(request()->action)}($model);
    }

    /**
     * @param $name
     * @return \Illuminate\Support\Stringable
     */
    protected function getActionMethod($name)
    {
        $exists = !! $this->getActions()->first(function ($action) use ($name) {
            return $action == $name;
        });

        return $exists
            ? (string) Str::of($name)->studly()->prepend('action')
            : null;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function getActions()
    {
        return $this->getActionMethods()->map(function ($method) {
            return (string) Str::of($method)->remove('action')->camel();
        });
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function getActionMethods()
    {
        return collect(get_class_methods($this))->filter(function ($method) {
            return Str::of($method)->startsWith('action')
                && $method !== 'action';
        })->values();
    }
}
