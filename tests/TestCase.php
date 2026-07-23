<?php

namespace Weap\Junction\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Weap\Junction\JunctionServiceProvider;
use Weap\Junction\Tests\TestSupport\Controllers\CommentController;
use Weap\Junction\Tests\TestSupport\Controllers\ForcedPaginationPostController;
use Weap\Junction\Tests\TestSupport\Controllers\GatedPostController;
use Weap\Junction\Tests\TestSupport\Controllers\PostController;
use Weap\Junction\Tests\TestSupport\Controllers\TagController;
use Weap\Junction\Tests\TestSupport\Controllers\UserController;
use Weap\Junction\Tests\TestSupport\Models\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get the package providers required for the test environment.
     *
     * @param Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
            JunctionServiceProvider::class,
        ];
    }

    /**
     * Define the environment setup.
     *
     * @param Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Define the routes used by the feature tests.
     *
     * @param Router $router
     * @return void
     */
    protected function defineRoutes($router): void
    {
        $router->junctionResource('comments', CommentController::class);
        $router->junctionResource('forced-posts', ForcedPaginationPostController::class);
        $router->junctionResource('gated-posts', GatedPostController::class);
        $router->junctionResource('posts', PostController::class);
        $router->junctionResource('tags', TagController::class);
        $router->junctionResource('users', UserController::class);
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained();
            $table->foreignId('tag_id')->constrained();
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }
}
