<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('returns a single record as a bare object', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Hello']);

    $this->getJson("/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('id', $post->id)
        ->assertJsonPath('title', 'Hello')
        ->assertJsonMissingPath('data')
        ->assertJsonMissingPath('items');
});

it('applies pluck to a shown record', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    $this->postJson("/posts/{$post->id}/show", ['pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('title', 'Hello')
        ->assertJsonPath('id', $post->id)
        ->assertJsonMissingPath('body');
});

it('eager loads relations on a shown record', function () {
    $post = Post::factory()->for($this->user)->create();

    $this->postJson("/posts/{$post->id}/show", ['with' => ['user']])
        ->assertOk()
        ->assertJsonPath('user.name', 'Ada');
});

it('returns 404 for a missing record', function () {
    $this->getJson('/posts/999')->assertNotFound();
});
