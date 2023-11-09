<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Validation\ValidationException;
use Weap\Junction\Http\Controllers\Controller;

class Relations
{
    /**
     * $relations should be an associative array where the key is the relation name and the value a callback.
     * Ex. `['relation' => function($query){ return $query; }]`
     *
     * @param Controller $controller
     * @param array $relations
     * @return array
     * @throws ValidationException
     */
    public static function validate(Controller $controller, array $relations)
    {
        $relations = collect($relations);

        if ($relations->isEmpty()) {
            return [];
        }

        $availableRelations = $controller->relations();

        if ($availableRelations == ['*']) {
            return $relations->all();
        }

        $relationsAreValid = $relations->every(function ($callback, $relation) use ($availableRelations) {
            $key = is_string($callback) ? $callback : $relation;

            return array_key_exists($key, $availableRelations) || in_array($key, $availableRelations);
        });

        throw_if(! $relationsAreValid, ValidationException::withMessages([
            'relations' => 'Invalid relations'
        ]));

        return $relations->all();
    }
}
