<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('deletes a record and returns the deleted model', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Doomed']);

    $this->deleteJson("/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('id', $post->id)
        ->assertJsonPath('title', 'Doomed');

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

it('returns 404 when deleting a missing record', function () {
    $this->deleteJson('/posts/999')->assertNotFound();
});
