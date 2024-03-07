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
    public static function resource($uri, $controller, $only = ['index', 'show', 'store', 'update', 'destroy', 'action']): void
    {
        Route::apiResource($uri, $controller)->only(array_diff($only, ['action']));

        if (in_array('index', $only)) {
            Route::post($uri . '/index', $controller. '@index');
        }

        if (in_array('show', $only)) {
            Route::post($uri . '/{id}/show', $controller. '@show');
        }

        if (in_array('action', $only)) {
            Route::put($uri, $controller . '@action');
        }
    }

    public function only($methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }
}
