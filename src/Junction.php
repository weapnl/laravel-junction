<?php

namespace Weap\Junction;

use Illuminate\Support\Facades\Route;

class Junction
{
    /**
     * @param $uri
     * @param $controller
     * @return void
     */
    public static function resource($uri, $controller): void
    {
        Route::get($uri, [$controller, 'index']);
        Route::get($uri . '/{id}', [$controller, 'show']);
        Route::post($uri, [$controller, 'store']);
        Route::put($uri . '/{id}', [$controller, 'update']);
        Route::delete($uri . '/{id}', [$controller, 'destroy']);

        Route::put($uri, [$controller, 'action']);
    }
}
