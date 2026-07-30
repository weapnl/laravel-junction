<?php

namespace Weap\Junction;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Route;
use Weap\Junction\Models\MediaTemporaryUpload;

class Junction
{
    /**
     * @return class-string<MediaTemporaryUpload>
     */
    public static function getMediaTemporaryUploadModel(): string
    {
        return config('junction.route.media.media_temporary_upload_model', MediaTemporaryUpload::class);
    }

    /**
     * @param string $uri
     * @param class-string $controller
     * @param array<int, string> $only
     * @return void
     *
     * @deprecated Replaced by Route::junctionResource().
     */
    public static function resource(string $uri, string $controller, array $only = ['index', 'indexPost', 'store', 'show', 'showPost', 'update', 'destroy', 'action']): void
    {
        Route::junctionResource($uri, $controller)->only($only);
    }

    /**
     * @param callable|null $get
     * @param callable|null $set
     * @param array<int|string, mixed> $with
     * @return Attribute<mixed, mixed>
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
