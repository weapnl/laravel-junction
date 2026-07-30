<?php

namespace Weap\Junction\Http\Controllers\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

class Table
{
    /**
     * @deprecated This function gives wrong table name for `morphTo` relation.
     *
     * @param class-string<Model> $model
     * @param array<int, string> $relations
     * @return string
     */
    public static function getRelationTableName(string $model, array $relations): string
    {
        $relation = static::getRelation($model, $relations);

        if ($relation instanceof BelongsToMany) {
            return $relation->getTable();
        }

        return $relation->newModelInstance()->getTable();
    }

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
