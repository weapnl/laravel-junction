<?php

namespace Weap\Junction;

use Illuminate\Support\Facades\Route;

/**
 * @deprecated Junction::resource are replaced by Route::junctionResource.
 */
class Junction
{
    /**
     * @param $uri
     * @param $controller
     * @param mixed $only
     * @return void
     */
    public static function resource($uri, $controller, $only = ['index', 'indexPost', 'store', 'show', 'showPost', 'update', 'destroy', 'action']): void
    {
        Route::junctionResource($uri, $controller)->only($only);
    }
}
