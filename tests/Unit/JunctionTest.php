<?php

use Illuminate\Database\Eloquent\Casts\Attribute;
use Weap\Junction\AttributeRelationCache;
use Weap\Junction\Junction;
use Weap\Junction\Models\MediaTemporaryUpload;
use Weap\Junction\Tests\TestSupport\Models\Post;

it('returns an Attribute instance', function () {
    expect(Junction::makeAttribute(fn () => 'x'))->toBeInstanceOf(Attribute::class);
});

it('caches the with-relations against the calling accessor method', function () {
    // Reading the accessor invokes Post::authorName(), which calls
    // Junction::makeAttribute(..., with: ['user']).
    (new Post())->author_name;

    expect(app(AttributeRelationCache::class)->get(Post::class, 'authorName'))->toBe(['user']);
});

it('returns the default media temporary upload model', function () {
    expect(Junction::getMediaTemporaryUploadModel())->toBe(MediaTemporaryUpload::class);
});

it('returns a configured media temporary upload model', function () {
    config()->set('junction.route.media.media_temporary_upload_model', 'App\\Custom\\Upload');

    expect(Junction::getMediaTemporaryUploadModel())->toBe('App\\Custom\\Upload');
});
