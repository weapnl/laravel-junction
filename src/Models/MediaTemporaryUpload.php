<?php

namespace Weap\Junction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_user_id');
    }
}
