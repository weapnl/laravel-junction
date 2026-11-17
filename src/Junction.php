<?php

namespace Weap\Junction;

use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Weap\Junction\Http\Resources\BaseResource;
use Weap\Junction\Models\MediaTemporaryUpload;

class Junction
{
    /**
     * @var (Closure(Model): (class-string<BaseResource>|null))|null
     */
    protected static ?Closure $resourceResolver = null;

    /**
     * Set the closure that returns the resource class for a related model. Return null to use the default resource. A
     * class that does not extend the base resource is ignored, and the default resource is used.
     *
     * @param (Closure(Model): (class-string<BaseResource>|null))|null $resolver
     * @return void
     */
    public static function resolveResourcesUsing(?Closure $resolver): void
    {
        static::$resourceResolver = $resolver;
    }

    /**
     * Get the resource class for the given model. Falls back to the base resource when the resolver returns null or a
     * class that does not extend it, because the response and the validators need its methods.
     *
     * @param Model $model
     * @return class-string<BaseResource>
     */
    public static function resourceFor(Model $model): string
    {
        $resource = static::$resourceResolver ? (static::$resourceResolver)($model) : null;

        return is_string($resource) && is_a($resource, BaseResource::class, true) ? $resource : BaseResource::class;
    }

    /**
     * @return class-string<MediaTemporaryUpload>
     *
     * @throws RuntimeException
     */
    public static function getMediaTemporaryUploadModel(): string
    {
        $model = config('junction.route.media.media_temporary_upload_model');

        if (! is_string($model) || ! is_a($model, MediaTemporaryUpload::class, true)) {
            throw new RuntimeException('The [junction.route.media.media_temporary_upload_model] config value must be a ' . MediaTemporaryUpload::class . ' class.');
        }

        return $model;
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
