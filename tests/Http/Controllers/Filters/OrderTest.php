<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('orders results ascending', function () {
    Post::factory()->for($this->user)->create(['title' => 'B']);
    Post::factory()->for($this->user)->create(['title' => 'A']);
    Post::factory()->for($this->user)->create(['title' => 'C']);

    $this->postJson('/posts/index', [
        'orders' => [['column' => 'title', 'direction' => 'asc']],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'A')
        ->assertJsonPath('items.1.title', 'B')
        ->assertJsonPath('items.2.title', 'C');
});

it('orders results descending', function () {
    Post::factory()->for($this->user)->create(['title' => 'B']);
    Post::factory()->for($this->user)->create(['title' => 'A']);
    Post::factory()->for($this->user)->create(['title' => 'C']);

    $this->postJson('/posts/index', [
        'orders' => [['column' => 'title', 'direction' => 'desc']],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'C')
        ->assertJsonPath('items.1.title', 'B')
        ->assertJsonPath('items.2.title', 'A');
});

it('throws when an order entry is missing its direction', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', [
        'orders' => [['column' => 'title']],
    ])->assertServerError();
});
