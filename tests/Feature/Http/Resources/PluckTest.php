<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('returns only the plucked attributes', function () {
    Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    $this->postJson('/posts/index', ['pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonMissingPath('items.0.body');
});

it('always includes the primary key when plucking', function () {
    $post = Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', ['pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('items.0.id', $post->id);
});

it('plucks nested relation fields', function () {
    Post::factory()->for($this->user)->create(['title' => 'Hello']);

    $this->postJson('/posts/index', [
        'with' => ['user'],
        'pluck' => ['title', 'user.name'],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonMissingPath('items.0.user.email');
});
