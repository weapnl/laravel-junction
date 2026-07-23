<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('applies a parameterless scope', function () {
    Post::factory()->for($this->user)->published()->create(['title' => 'Published']);
    Post::factory()->for($this->user)->create(['title' => 'Draft']);

    $this->postJson('/posts/index', [
        'scopes' => [['name' => 'published']],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Published');
});

it('applies a scope with parameters', function () {
    Post::factory()->for($this->user)->create(['title' => 'Laravel Junction']);
    Post::factory()->for($this->user)->create(['title' => 'Something else']);

    $this->postJson('/posts/index', [
        'scopes' => [['name' => 'titleContains', 'params' => ['Junction']]],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Laravel Junction');
});

it('rejects an unknown scope with a validation error', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', [
        'scopes' => [['name' => 'notARealScope']],
    ])->assertStatus(422);
});
