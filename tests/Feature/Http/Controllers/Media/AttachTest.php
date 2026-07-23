<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Weap\Junction\Tests\TestSupport\Models\MediaPost;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

/** Upload a fake image as the given user and return the new media id. */
function uploadPhoto(object $test): int
{
    return $test->post('/media/upload', [
        'files' => [UploadedFile::fake()->image('photo.jpg')],
    ])->json()[0];
}

it('attaches uploaded media to a stored model', function () {
    Storage::fake('public');
    $this->actingAs($this->user);

    $mediaId = uploadPhoto($this);

    $this->postJson('/media-posts', [
        'title' => 'Gallery',
        '_media' => ['photos' => [$mediaId]],
    ])->assertOk();

    $post = MediaPost::first();
    expect($post->getMedia('photos'))->toHaveCount(1);

    // The media was moved off the temporary upload, which is then removed.
    $this->assertDatabaseCount('media_temporary_uploads', 0);
});

it('attaches media when using the local disk', function () {
    config()->set('media-library.disk_name', 'local');
    Storage::fake('local');
    $this->actingAs($this->user);

    $mediaId = uploadPhoto($this);

    $this->postJson('/media-posts', [
        'title' => 'Local Gallery',
        '_media' => ['photos' => [$mediaId]],
    ])->assertOk();

    expect(MediaPost::first()->getMedia('photos'))->toHaveCount(1);
});

it('forbids attaching media owned by another user', function () {
    Storage::fake('public');

    // Ada uploads.
    $this->actingAs($this->user);
    $mediaId = uploadPhoto($this);

    // Bob tries to attach Ada's temporary upload.
    $bob = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    $this->actingAs($bob);

    $this->postJson('/media-posts', [
        'title' => 'Stolen',
        '_media' => ['photos' => [$mediaId]],
    ])->assertNotFound();

    expect(MediaPost::first()?->getMedia('photos'))->toBeNull();
});
