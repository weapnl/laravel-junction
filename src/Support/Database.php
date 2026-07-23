<?php

namespace Weap\Junction\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Weap\Junction\Enums\DatabaseTransactionType;

class Database
{
    /**
     * @param DatabaseTransactionType $type
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    protected static function runInTransactionIfEnabled(DatabaseTransactionType $type, callable $callback): mixed
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
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Store, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function updateInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Update, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function destroyInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Destroy, $callback);
    }

    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function actionInTransactionIfEnabled(callable $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Action, $callback);
    }
}
