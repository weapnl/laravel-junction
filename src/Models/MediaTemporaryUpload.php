<?php

namespace Weap\Junction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int|null $created_by_user_id
 */
class MediaTemporaryUpload extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * @return BelongsTo<Model, $this>
     *
     * @throws RuntimeException
     */
    public function createdBy(): BelongsTo
    {
        $userModel = config('auth.providers.users.model');

        if (! is_string($userModel) || ! is_subclass_of($userModel, Model::class)) {
            throw new RuntimeException('The [auth.providers.users.model] config value must be an Eloquent model class.');
        }

        return $this->belongsTo($userModel, 'created_by_user_id');
    }
}
