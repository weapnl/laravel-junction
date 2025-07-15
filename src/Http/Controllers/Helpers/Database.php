<?php

namespace Weap\Junction\Http\Controllers\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Weap\Junction\Http\Controllers\Enums\DatabaseTransactionTypeEnum;

class Database
{
    /**
     * @param DatabaseTransactionTypeEnum $type
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    protected static function runInTransactionIfEnabled(DatabaseTransactionTypeEnum $type, callable $callback): mixed
    {
        if (config('junction.use_db_transactions.' . Str::snake($type->name))) {
            return DB::transaction($callback);
        }

        return $callback();
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function storeInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionTypeEnum::store, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function updateInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionTypeEnum::update, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function destroyInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionTypeEnum::destroy, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function actionInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionTypeEnum::action, $callback);
    }
}
