<?php

namespace Weap\Junction;

use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Weap\Junction\Http\Controllers\Resources\BaseResource;
use Weap\Junction\Models\MediaTemporaryUpload;

class Junction
{
    /**
     * @var (Closure(Model): (class-string<BaseResource>|null))|null
     */
    protected static ?Closure $resourceResolver = null;

    /**
     * Set the closure that returns the resource class for a related model. Return null to use the default resource.
     *
     * @param (Closure(Model): (class-string<BaseResource>|null))|null $resolver
     * @return void
     */
    public static function resolveResourcesUsing(?Closure $resolver): void
    {
        static::$resourceResolver = $resolver;
    }

    /**
     * @param Model $model
     * @return class-string<BaseResource>
     */
    public static function resourceFor(Model $model): string
    {
        return (static::$resourceResolver ? (static::$resourceResolver)($model) : null) ?? BaseResource::class;
    }

    /**
     * @return class-string<MediaTemporaryUpload>
     */
    public static function getMediaTemporaryUploadModel(): string
    {
        return config('junction.route.media.media_temporary_upload_model', MediaTemporaryUpload::class);
    }

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
