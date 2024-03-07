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
        Route::apiResource($uri, $controller);
        Route::post($uri . '/index', $controller. '@index');
        Route::post($uri . '/{id}/show', $controller. '@show');
        Route::put($uri, [$controller, 'action']);
    }
}
