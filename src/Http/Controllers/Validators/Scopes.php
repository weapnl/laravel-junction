<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Validation\ValidationException;
use Weap\Junction\Http\Controllers\Controller;

class Scopes
{
    /**
     * @param Controller $controller
     * @param array $scopes
     * @return array
     * @throws ValidationException
     */
    public static function validate(Controller $controller, array $scopes): array
    {
        $scopeCollection = collect($scopes);

        if ($scopeCollection->isEmpty()) {
            return [];
        }

        $filteredScopes = $scopeCollection->filter(function ($scope) use ($controller) {
            return app($controller->model)::query()->hasNamedScope($scope['name']);
        });

        if ($filteredScopes->count() !== $scopeCollection->count()) {
            throw ValidationException::withMessages([
                'scopes' => 'Invalid scopes'
            ]);
        }

        return $scopeCollection->toArray();
    }
}
