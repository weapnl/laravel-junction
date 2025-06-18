<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Support\Arr;
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
     *
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

        $availableRelationsUndotted = Arr::undot(array_flip($availableRelations));
        $invalidRelations = $relations->filter(function ($callback, $relation) use ($availableRelations, $availableRelationsUndotted) {
            $key = is_string($callback) ? $callback : $relation;

            // Direct match
            if (in_array($key, $availableRelations)) {
                return false;
            }

            return ! Arr::has($availableRelationsUndotted, $key);
        });

        throw_if($invalidRelations->isNotEmpty(), ValidationException::withMessages([
            'relations' => "Invalid relation(s): {$invalidRelations->join(', ')}",
        ]));

        return $relations->all();
    }
}
