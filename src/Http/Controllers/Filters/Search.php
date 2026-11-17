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
use Weap\Junction\Support\Table;

class Search extends Filter
{
    /**
     * @param Controller $controller
     * @param Builder<Model>|Relation<Model, Model, mixed> $query
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
        $fromRequest = ! empty($requestColumns);

        $query->where(function (Builder $query) use ($searchValue, $model, $columns, $fromRequest) {
            $columns = Arr::undot(array_flip($columns));

            self::searchColumnQuery($query, $columns, $model->getTable(), $searchValue, $fromRequest);
        });
    }

    /**
     * Each relation and column is checked on the model of the query it is used on, just before it is used. Behind a
     * `morphTo` relation, Laravel runs the nested query once for each related model type, so the rest of the path is
     * checked on each of those models.
     *
     * @param Builder<Model> $query
     * @param array<string, mixed> $columns
     * @param string $tableName
     * @param string $searchValue
     * @param bool $fromRequest Whether the columns come from the request, so every relation must be a declared relation and
     *                          every column a plain name that is not hidden.
     * @param Model|null $columnModel The model whose table holds the columns; the model of the query when not given.
     * @return void
     */
    public static function searchColumnQuery(Builder $query, array $columns, string $tableName, string $searchValue, bool $fromRequest = false, ?Model $columnModel = null): void
    {
        $connection = DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $likeOperator = $connection === 'pgsql' ? 'ILIKE' : 'LIKE';
        $columnModel ??= $query->getModel();

        foreach ($columns as $relationName => $relationColumns) {
            if (! is_array($relationColumns)) {
                if ($fromRequest) {
                    static::validateColumn($columnModel, (string) $relationName, 'search_columns');
                }

                $query->orWhere($tableName . '.' . $relationName, $likeOperator, '%' . $searchValue . '%');

                continue;
            }

            if ($fromRequest) {
                static::validateRelationPath($query, (string) $relationName, 'search_columns');
            }

            $relation = Table::getRelation($query->getModel()::class, [$relationName]);

            $query->orWhereHas($relationName, function (Builder $query) use ($relationColumns, $relation, $searchValue, $fromRequest) {
                $tableName = $relation instanceof BelongsToMany ? $relation->getTable() : $query->getModel()->getTable();
                $columnModel = static::columnModel($relation, $query);

                $query->where(function (Builder $query) use ($relationColumns, $tableName, $searchValue, $fromRequest, $columnModel) {
                    self::searchColumnQuery($query, $relationColumns, $tableName, $searchValue, $fromRequest, $columnModel);
                });
            });
        }
    }
}
