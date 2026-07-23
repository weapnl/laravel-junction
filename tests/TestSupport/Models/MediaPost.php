<?php

namespace Weap\Junction\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MediaPost extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = ['title'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }
}
