<?php

namespace Weap\Junction\Http\Controllers\Helpers;

use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionHelper
{
    /**
     * @param callable $callback
     * @return void
     *
     * @throws Throwable
     */
    public static function runInTransactionIfEnabled(callable $callback): void
    {
        if (config('junction.use_db_transactions')) {
            DB::transaction($callback);

            return;
        }

        $callback();
    }
}
