<?php

namespace Weap\Junction\Http\Controllers\Helpers;

use Illuminate\Support\Facades\DB;
use Throwable;
use Weap\Junction\Http\Controllers\Enums\TransactionTypeEnum;

class Database
{
    /**
     * @param TransactionTypeEnum $type
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    protected static function runInTransactionIfEnabled(TransactionTypeEnum $type, callable $callback): mixed
    {
        if (config('junction.use_db_transactions.' . $type->name)) {
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
        return self::runInTransactionIfEnabled(TransactionTypeEnum::store, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function updateInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(TransactionTypeEnum::update, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function destroyInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(TransactionTypeEnum::destroy, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function uploadInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(TransactionTypeEnum::upload, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function actionInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(TransactionTypeEnum::action, $callback);
    }
}
