<?php

namespace Weap\Junction\Http\Controllers\Helpers;

use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionHelper
{
    /**
     * @param callable $callback
     * @return mixed
     *
     * @throws Throwable
     */
    public static function runInTransactionIfEnabled(callable $callback): mixed
    {
        if (config('junction.use_db_transactions')) {
            return DB::transaction($callback);
        }

        return $callback();
    }
}
