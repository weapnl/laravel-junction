<?php

namespace Weap\Junction;

use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Weap\Junction\Commands\CleanMediaTemporaryUploads;

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

        $this->commands([
            CleanMediaTemporaryUploads::class,
        ]);

        if (class_exists(Media::class) && config('junction.route.media.enabled', true)) {
            Route::middleware(config('junction.route.media.middleware', ['api']))
                ->prefix(config('junction.route.media.prefix', ''))
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
