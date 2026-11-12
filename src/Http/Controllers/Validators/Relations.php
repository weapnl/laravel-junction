<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Weap\Junction\Extensions\RelationExtension;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\RelationFinder;

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

        $model = new $controller->model();

        $invalidRelations = $relations->filter(function ($callback, $relation) use ($availableRelations, $model) {
            $key = is_string($callback) ? $callback : $relation;

            if (! is_string($key)) {
                return true;
            }

            // Any relation is allowed, but eager loading calls the method of every name, so it must be a declared relation.
            if ($availableRelations == ['*']) {
                return ! RelationFinder::isRelationPath($model, $key);
            }

            return ! Arr::first($availableRelations, static fn ($relation, $value) => static::includesPath(is_string($relation) ? $relation : $value, $key));
        });

        throw_if($invalidRelations->isNotEmpty(), ValidationException::withMessages([
            'relations' => "Invalid relation(s): {$invalidRelations->join(', ')}",
        ]));

        return $relations->all();
    }

    /**
     * A relation that is allowed also allows each relation on its path, e.g. `user.orders` allows `user`.
     *
     * @param string $availableRelation
     * @param string $relation
     * @return bool
     */
    protected static function includesPath(string $availableRelation, string $relation): bool
    {
        return $availableRelation === $relation || Str::startsWith($availableRelation, $relation . '.');
    }
}
