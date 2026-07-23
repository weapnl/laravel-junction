<?php

use Weap\Junction\Tests\TestSupport\Models\Comment;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('eager loads a single relation', function () {
    Post::factory()->for($this->user)->create(['title' => 'Hello']);

    $this->postJson('/posts/index', ['with' => ['user']])
        ->assertOk()
        ->assertJsonPath('items.0.user.name', 'Ada');
});

it('eager loads a nested relation with dot notation', function () {
    $post = Post::factory()->for($this->user)->create();
    Comment::factory()->for($post)->for($this->user)->create(['body' => 'Nice post']);

    $this->postJson('/posts/index', ['with' => ['comments.user']])
        ->assertOk()
        ->assertJsonPath('items.0.comments.0.body', 'Nice post')
        ->assertJsonPath('items.0.comments.0.user.name', 'Ada');
});

it('applies a controller relation closure to the eager loaded relation', function () {
    $post = Post::factory()->for($this->user)->create();

    // PostController constrains "comments" with ->latest().
    $older = Comment::factory()->for($post)->for($this->user)->create(['body' => 'older']);
    $newer = Comment::factory()->for($post)->for($this->user)->create(['body' => 'newer']);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    $newer->forceFill(['created_at' => now()])->save();

    $this->postJson('/posts/index', ['with' => ['comments']])
        ->assertOk()
        ->assertJsonPath('items.0.comments.0.body', 'newer')
        ->assertJsonPath('items.0.comments.1.body', 'older');
});

it('does not include relations that were not requested', function () {
    Post::factory()->for($this->user)->create();

    $this->getJson('/posts')
        ->assertOk()
        ->assertJsonMissingPath('items.0.user');
});

it('rejects a relation that is not whitelisted', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', ['with' => ['secretRelation']])
        ->assertStatus(422);
});
