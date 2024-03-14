<?php

namespace Weap\Junction;

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class JunctionServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        Route::macro('junctionResource', function ($name, $controller, array $options = []) {
            $defaults = ['index', 'indexPost', 'store', 'show', 'showPost', 'update', 'destroy', 'action'];

            $only = $options['only'] ?? $defaults;

            if (isset($options['except'])) {
                $only = array_diff($only, (array) $options['except']);
            }

            $registrar = new ResourceRegistrar($this);

            return new PendingResourceRegistration(
                $registrar,
                $name,
                $controller,
                array_merge(['only' => $only], $options)
            );
        });
    }
}
