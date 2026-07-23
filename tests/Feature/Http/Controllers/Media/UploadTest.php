<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Weap\Junction\Models\MediaTemporaryUpload;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $this->actingAs($this->user);
});

it('uploads temporary media and returns the created media ids', function () {
    $response = $this->post('/media/upload', [
        'files' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    $response->assertOk();
    expect($response->json())->toBeArray()->toHaveCount(1);

    $this->assertDatabaseCount('media_temporary_uploads', 1);
    $this->assertDatabaseCount('media', 1);
    expect(MediaTemporaryUpload::first()->created_by_user_id)->toBe($this->user->id);
});

it('uploads multiple files at once', function () {
    $response = $this->post('/media/upload', [
        'files' => [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
        ],
    ]);

    $response->assertOk();
    expect($response->json())->toHaveCount(2);
    $this->assertDatabaseCount('media', 2);
});

it('requires at least one file', function () {
    $this->postJson('/media/upload', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('files');
});
