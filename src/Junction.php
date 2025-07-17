<?php

namespace Weap\Junction;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Route;

class Junction
{
    /**
     * @param $uri
     * @param $controller
     * @param mixed $only
     * @return void
     *
     * @deprecated Replaced by Route::junctionResource().
     */
    public static function resource($uri, $controller, $only = ['index', 'indexPost', 'store', 'show', 'showPost', 'update', 'destroy', 'action']): void
    {
        Route::junctionResource($uri, $controller)->only($only);
    }

    /**
     * @param callable|null $get
     * @param callable|null $set
     * @param array $with
     * @return Attribute
     */
    public static function makeAttribute(?callable $get = null, ?callable $set = null, array $with = []): Attribute
    {
        $attribute = Attribute::make($get, $set);

        if ($caller = debug_backtrace()[1] ?? null) {
            app(AttributeRelationCache::class)->set($caller['class'], $caller['function'], $with);
        }

        return $attribute;
    }
}
