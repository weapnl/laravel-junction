<?php

namespace Weap\Junction\Http\Controllers\Helpers;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Table
{
    /**
     * @param string $model
     * @param array $relations
     * @return string
     */
    public static function getRelationTableName(string $model, array $relations): string
    {
        if (count($relations) > 1) {
            $relation = array_shift($relations);

            return static::getRelationTableName(
                (new $model())->$relation()->newModelInstance()::class,
                $relations
            );
        }

        $relation = (new $model())->{$relations[0]}();

        if ($relation instanceof BelongsToMany) {
            return $relation->getTable();
        }

        return $relation->newModelInstance()->getTable();
    }
}
