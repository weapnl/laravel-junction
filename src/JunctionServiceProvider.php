<?php

namespace Weap\Junction;

use Illuminate\Http\Request;
use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Weap\Junction\Commands\CleanMediaTemporaryUploads;
use Weap\Junction\Extensions\RelationExtension;

class JunctionServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        // Merged recursively so a published config file only has to contain the values it overrides,
        // and keeps inheriting the package defaults for every nested key it does not mention.
        $this->replaceConfigRecursivelyFrom(__DIR__ . '/../config/junction.php', 'junction');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'junction-migrations');

        $this->publishes([
            __DIR__ . '/../config/junction.php' => config_path('junction.php'),
        ], 'junction-config');

        $this->commands([
            CleanMediaTemporaryUploads::class,
        ]);

        if (class_exists(Media::class) && config()->boolean('junction.route.media.enabled')) {
            Route::middleware(config('junction.route.media.middleware'))
                ->prefix(config('junction.route.media.prefix'))
                ->group(__DIR__ . '/../routes/media_library.php');
        }

        $this->bootRouteMacros();

        $this->bootRequestMacros();

        $this->app->scoped(RelationExtension::class, fn () => new RelationExtension());

        $this->app->scoped(AttributeRelationCache::class, fn () => new AttributeRelationCache());
    }

    /**
     * @return void
     */
    protected function bootRouteMacros(): void
    {
        Route::macro('junctionResource', function ($name, $controller, array $options = []) {
            $only = $options['only'] ?? ResourceRegistrar::DEFAULT_METHODS;

            if (isset($options['except'])) {
                $only = array_diff($only, (array) $options['except']);
            }

            $registrar = new ResourceRegistrar(app(Router::class));

            return new PendingResourceRegistration(
                $registrar,
                $name,
                $controller,
                array_merge(['only' => $only], $options)
            );
        });
    }

    /**
     * @return void
     */
    protected function bootRequestMacros(): void
    {
        Request::macro('getPluckFields', fn () => $this->input('pluck'));

        Request::macro('getAccessors', fn () => $this->input('appends'));

        Request::macro('getRelations', function () {
            $relations = $this->input('with');

            // Accessors and counts using dot notation require their relation to be loaded as well.
            $fields = array_merge(
                $this->getAccessors() ?? [],
                $this->input('count') ?? [],
            );

            foreach ($fields as $field) {
                if (! is_string($field) || ! Str::contains($field, '.')) {
                    continue;
                }

                $fieldRelation = Str::beforeLast($field, '.');

                if (! Arr::first($relations ?? [], fn ($relation) => Str::startsWith($relation, $fieldRelation))) {
                    $relations ??= [];
                    $relations[] = $fieldRelation;
                }
            }

            return $relations;
        });
    }
}
