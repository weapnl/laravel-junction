<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Weap\Junction\Extensions\RelationExtension;
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

        $availableRelations = app(RelationExtension::class)->call($controller->relations() ?? [], $controller);

        if ($availableRelations == ['*']) {
            return $relations->all();
        }

        $invalidRelations = $relations->filter(function ($callback, $relation) use ($availableRelations) {
            $key = is_string($callback) ? $callback : $relation;

            return ! Arr::first($availableRelations, static fn ($relation, $value) => Str::startsWith(is_string($relation) ? $relation : $value, $key));
        });

        throw_if($invalidRelations->isNotEmpty(), ValidationException::withMessages([
            'relations' => "Invalid relation(s): {$invalidRelations->join(', ')}",
        ]));

        return $relations->all();
    }
}
