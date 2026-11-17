<?php

namespace Weap\Junction\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;
use Weap\Junction\Enums\DatabaseTransactionType;

class Database
{
    /**
     * @template TReturn
     *
     * @param DatabaseTransactionType $type
     * @param Closure(): TReturn $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    protected static function runInTransactionIfEnabled(DatabaseTransactionType $type, Closure $callback): mixed
    {
        if (config('junction.use_db_transactions.' . $type->value)) {
            return DB::transaction($callback);
        }

        return $callback();
    }

    /**
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public static function storeInTransactionIfEnabled(Closure $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Store, $callback);
    }

    /**
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public static function updateInTransactionIfEnabled(Closure $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Update, $callback);
    }

    /**
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public static function destroyInTransactionIfEnabled(Closure $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Destroy, $callback);
    }

    /**
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public static function actionInTransactionIfEnabled(Closure $callback): mixed
    {
        return self::runInTransactionIfEnabled(DatabaseTransactionType::Action, $callback);
    }
}
