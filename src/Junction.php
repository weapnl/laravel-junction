<?php

namespace Weap\Junction;

use Illuminate\Database\Eloquent\Casts\Attribute;
use RuntimeException;
use Weap\Junction\Models\MediaTemporaryUpload;

class Junction
{
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
