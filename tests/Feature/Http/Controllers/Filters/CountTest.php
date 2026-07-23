<?php

use Weap\Junction\Tests\TestSupport\Models\Comment;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('adds a relation count to each record', function () {
    $post = Post::factory()->for($this->user)->create();
    Comment::factory()->count(2)->for($post)->for($this->user)->create();

    $this->postJson('/posts/index', [
        'count' => ['comments'],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.comments_count', 2);
});

it('rejects counting an unavailable relation', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', [
        'count' => ['nonExistingRelation'],
    ])->assertStatus(422);
});
