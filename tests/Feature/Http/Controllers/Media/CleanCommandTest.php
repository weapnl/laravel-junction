<?php

use Weap\Junction\Models\MediaTemporaryUpload;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

function makeTemporaryUpload(int $userId, string $createdAt): MediaTemporaryUpload
{
    $upload = new MediaTemporaryUpload();
    $upload->forceFill(['created_by_user_id' => $userId, 'created_at' => $createdAt])->save();

    return $upload;
}

it('deletes temporary uploads older than the cutoff', function () {
    $old = makeTemporaryUpload($this->user->id, now()->subHours(48)->toDateTimeString());
    $recent = makeTemporaryUpload($this->user->id, now()->toDateTimeString());

    $this->artisan('media:clean-media-temporary-uploads', ['hours' => 24])
        ->assertSuccessful();

    expect(MediaTemporaryUpload::find($old->id))->toBeNull();
    expect(MediaTemporaryUpload::find($recent->id))->not->toBeNull();
});

it('respects the hours argument', function () {
    $upload = makeTemporaryUpload($this->user->id, now()->subHours(10)->toDateTimeString());

    // Cutoff of 12h keeps a 10h-old record.
    $this->artisan('media:clean-media-temporary-uploads', ['hours' => 12])
        ->assertSuccessful();
    expect(MediaTemporaryUpload::find($upload->id))->not->toBeNull();

    // Cutoff of 6h removes it.
    $this->artisan('media:clean-media-temporary-uploads', ['hours' => 6])
        ->assertSuccessful();
    expect(MediaTemporaryUpload::find($upload->id))->toBeNull();
});
