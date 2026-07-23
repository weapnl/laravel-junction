<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('hides a requested field', function () {
    Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    $this->postJson('/posts/index', ['hidden_fields' => ['body']])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonMissingPath('items.0.body');
});

it('hides a nested relation field', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', [
        'with' => ['user'],
        'hidden_fields' => ['user.email'],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonMissingPath('items.0.user.email');
});
