<?php

namespace Weap\Junction\Http\Controllers\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ReflectionMethod;
use Throwable;
use Weap\Junction\Http\Controllers\Helpers\Database;

trait HasAction
{
    /**
     * @return mixed
     *
     * @throws Throwable
     */
    public function action(): mixed
    {
        request()->validate([
            'action' => [
                'required',
                Rule::in($this->getActions()),
            ],
        ]);

        $actionMethod = $this->getActionMethod((string) request()->action);
        $requiresModel = $this->actionRequiresModel($actionMethod);

        if ($requiresModel) {
            request()->validate([
                'id' => ['required'],
            ]);
        }

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

        return Database::actionInTransactionIfEnabled(fn () => $this->{$actionMethod}($model));
    }

    /**
     * @param string $name
     * @return string|null
     */
    protected function getActionMethod(string $name): ?string
    {
        $exists = (bool) $this->getActions()->first(function (string $action) use ($name) {
            return $action === $name;
        });

        return $exists
            ? (string) Str::of($name)->studly()->prepend('action')
            : null;
    }

    /**
     * @return Collection<int, string>
     */
    protected function getActions(): Collection
    {
        return $this->getActionMethods()->map(function (string $method) {
            return (string) Str::of($method)->remove('action')->camel();
        });
    }

    /**
     * @return Collection<int, string>
     */
    protected function getActionMethods(): Collection
    {
        return collect(get_class_methods($this))->filter(function ($method) {
            return Str::of($method)->startsWith('action')
                && $method !== 'action';
        })->values();
    }

    /**
     * Check if an action method has a typed (non-nullable) first parameter,
     * meaning it requires a model to be passed.
     *
     * @param string|null $method
     * @return bool
     */
    private function actionRequiresModel(?string $method): bool
    {
        if (! $method || ! method_exists($this, $method)) {
            return false;
        }

        $parameters = (new ReflectionMethod($this, $method))->getParameters();

        return ! empty($parameters) && $parameters[0]->hasType() && ! $parameters[0]->allowsNull();
    }
}
