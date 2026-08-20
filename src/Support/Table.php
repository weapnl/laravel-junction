<?php

namespace Weap\Junction\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Table
{
    /**
     * @param class-string<Model> $model
     * @param array<int, string> $relations
     * @return Relation<Model, Model, mixed>
     */
    public static function getRelation(string $model, array $relations): Relation
    {
        if (count($relations) > 1) {
            $relation = array_shift($relations);

            return static::getRelation(
                (new $model())->$relation()->newModelInstance()::class,
                $relations
            );
        }

        return (new $model())->{$relations[0]}();
    }
}
