<?php

use Illuminate\Support\Facades\Route;
use Weap\Junction\Tests\TestSupport\Controllers\PostController;

/** @return list<string> "VERB uri" signatures for routes whose uri starts with $prefix. */
function routeSignatures(string $prefix): array
{
    return collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), $prefix))
        ->flatMap(fn ($route) => collect($route->methods())->map(fn ($verb) => $verb . ' ' . $route->uri()))
        ->values()
        ->all();
}

it('registers the full set of junction resource routes', function () {
    // "posts" is registered in TestCase::defineRoutes.
    $signatures = routeSignatures('posts');

    expect($signatures)
        ->toContain('GET posts')            // index
        ->toContain('POST posts/index')     // indexPost
        ->toContain('POST posts')           // store
        ->toContain('GET posts/{post}')     // show
        ->toContain('POST posts/{post}/show') // showPost
        ->toContain('PUT posts/{post}')     // update
        ->toContain('PATCH posts/{post}')   // update
        ->toContain('DELETE posts/{post}')  // destroy
        ->toContain('PUT posts');           // action
});

it('honours the only option', function () {
    Route::junctionResource('widgets', PostController::class, ['only' => ['index', 'show']]);

    $signatures = routeSignatures('widgets');

    expect($signatures)
        ->toContain('GET widgets')
        ->toContain('GET widgets/{widget}')
        ->not->toContain('POST widgets')          // store pruned
        ->not->toContain('DELETE widgets/{widget}'); // destroy pruned
});

it('honours the except option', function () {
    Route::junctionResource('gadgets', PostController::class, ['except' => ['destroy', 'action']]);

    $signatures = routeSignatures('gadgets');

    expect($signatures)
        ->toContain('GET gadgets')
        ->toContain('POST gadgets')                 // store kept
        ->not->toContain('DELETE gadgets/{gadget}') // destroy pruned
        ->not->toContain('PUT gadgets');            // action pruned
});
