<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('updates a record and returns it', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Old']);

    $this->putJson("/posts/{$post->id}", ['title' => 'New'])
        ->assertOk()
        ->assertJsonPath('id', $post->id)
        ->assertJsonPath('title', 'New');

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'New']);
});

it('supports PATCH as well as PUT', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Old']);

    $this->patchJson("/posts/{$post->id}", ['title' => 'Patched'])
        ->assertOk()
        ->assertJsonPath('title', 'Patched');
});

it('returns 404 when updating a missing record', function () {
    $this->putJson('/posts/999', ['title' => 'Nope'])->assertNotFound();
});

it('rejects an update that fails validation', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Old']);

    $this->putJson("/posts/{$post->id}", ['title' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Old']);
});

it('runs the before and after update hooks', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Old']);

    $this->putJson("/hooked-posts/{$post->id}", ['title' => 'updated'])
        ->assertOk()
        ->assertJsonPath('title', 'UPDATED')
        ->assertJsonPath('body', 'after-update-hook');
});
