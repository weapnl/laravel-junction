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
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../config/junction.php' => config_path('junction.php'),
        ]);

        if (class_exists(\Spatie\MediaLibrary\MediaCollections\Models\Media::class)) {
            Route::middleware(config('junction.route.middleware', ['api']))
                ->prefix(config('junction.route.prefix', ''))
                ->group(__DIR__ . '/../routes/media_library.php');
        }

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
