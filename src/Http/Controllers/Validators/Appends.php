<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Weap\Junction\Http\Controllers\Controller;

class Appends
{
    /**
     * @param Controller $controller
     * @param array $appends
     * @return array
     *
     * @throws ValidationException
     */
    public static function validate(Controller $controller, array $appends)
    {
        $appends = collect($appends);

        if ($appends->isEmpty()) {
            return [];
        }

        $model = new $controller->model();

        $check = $appends->count() == $appends->filter(function ($append) use ($model) {
            if (Str::contains($append, '.')) {
                // TODO Validate relation appends

                return true;
            }

                return $model->hasGetMutator($append) || $model->hasAttributeGetMutator($append);
            })->count();

        if ($check) {
            return $appends->toArray();
        }

        throw ValidationException::withMessages([
            'appends' => 'Invalid appends',
        ]);
    }
}
