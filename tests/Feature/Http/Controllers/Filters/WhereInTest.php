<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('filters a direct column by a set of values', function () {
    Post::factory()->for($this->user)->create(['title' => 'A']);
    Post::factory()->for($this->user)->create(['title' => 'B']);
    Post::factory()->for($this->user)->create(['title' => 'C']);

    $this->postJson('/posts/index', [
        'where_in' => [['column' => 'title', 'values' => ['A', 'C']]],
    ])
        ->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('items.0.title', 'A')
        ->assertJsonPath('items.1.title', 'C');
});

it('filters a relation column by a set of values', function () {
    $bob = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    Post::factory()->for($this->user)->create(['title' => 'By Ada']);
    Post::factory()->for($bob)->create(['title' => 'By Bob']);

    $this->postJson('/posts/index', [
        'where_in' => [['column' => 'user.name', 'values' => ['Bob']]],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'By Bob');
});
