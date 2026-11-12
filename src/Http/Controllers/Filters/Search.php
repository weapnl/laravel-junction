<?php

namespace Weap\Junction\Http\Controllers\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PDO;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\Table;

class Search extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder|Relation $query
     */
    public static function apply(Controller $controller, Builder|Relation $query): void
    {
        /** @var Model $model */
        $model = app($controller->model);

        $searchValue = request()->input('search_value');
        $requestColumns = request()->input('search_columns');
        $columns = empty($requestColumns) ? $controller->searchable() : $requestColumns;

        if (empty($searchValue) || empty($columns)) {
            return;
        }

        $searchValue = $controller->mutateSearchValue($searchValue);

        // The controller's searchable columns are trusted; only columns from the request must be checked.
        $validateRelations = ! empty($requestColumns);

        $query->where(function (Builder $query) use ($searchValue, $model, $columns, $validateRelations) {
            $columns = Arr::undot(array_flip($columns));

            self::searchColumnQuery($query, $columns, $model->getTable(), $searchValue, $validateRelations);
        });
    }

    /**
     * Each relation is checked on the model of the query it is used on, just before its method is called. Behind a
     * `morphTo` relation, Laravel runs the nested query once for each related model type, so the rest of the path is
     * checked on each of those models.
     *
     * @param Builder $query
     * @param array $columns
     * @param string $tableName
     * @param string $searchValue
     * @param bool $validateRelations Whether every relation must be a declared relation, for columns from the request.
     * @return void
     */
    public static function searchColumnQuery(Builder $query, array $columns, string $tableName, string $searchValue, bool $validateRelations = false): void
    {
        $connection = DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $likeOperator = $connection === 'pgsql' ? 'ILIKE' : 'LIKE';

        foreach ($columns as $relationName => $relationColumns) {
            if (! is_array($relationColumns)) {
                $query->orWhere($tableName . '.' . $relationName, $likeOperator, '%' . $searchValue . '%');
            } else {
                if ($validateRelations) {
                    static::validateRelationPath($query, (string) $relationName, 'search_columns');
                }

                $relation = Table::getRelation($query->getModel()::class, [$relationName]);

                $query->orWhereHas($relationName, function (Builder $query) use ($relationColumns, $relation, $searchValue, $validateRelations) {
                    $tableName = $relation instanceof BelongsToMany ? $relation->getTable() : $query->getModel()->getTable();

                    $query->where(function (Builder $query) use ($relationColumns, $tableName, $searchValue, $validateRelations) {
                        self::searchColumnQuery($query, $relationColumns, $tableName, $searchValue, $validateRelations);
                    });
                });
            }
        }
    }
}
