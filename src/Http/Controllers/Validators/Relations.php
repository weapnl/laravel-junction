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

        $invalidRelations = [];

        $relations->each(function ($callback, $relation) use ($availableRelations, &$invalidRelations) {
            $key = is_string($callback) ? $callback : $relation;

            if (! array_key_exists($key, $availableRelations) && ! in_array($key, $availableRelations)) {
                $invalidRelations[] = $key;
            }
        });

        if (! empty($invalidRelations)) {
            $invalidRelationsString = implode(', ', $invalidRelations);
            throw ValidationException::withMessages([
                'relations' => "Invalid relation(s): $invalidRelationsString",
            ]);
        }


        return $relations->all();
    }
}
