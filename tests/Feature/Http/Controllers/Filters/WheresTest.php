<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\Tag;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('filters with an equality operator', function () {
    Post::factory()->for($this->user)->create(['title' => 'Keep']);
    Post::factory()->for($this->user)->create(['title' => 'Drop']);

    $this->postJson('/posts/index', [
        'wheres' => [['column' => 'title', 'operator' => '=', 'value' => 'Keep']],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Keep');
});

it('treats a null value as whereNull', function () {
    Post::factory()->for($this->user)->published()->create(['title' => 'Published']);
    Post::factory()->for($this->user)->create(['title' => 'Draft', 'published_at' => null]);

    $this->postJson('/posts/index', [
        'wheres' => [['column' => 'published_at', 'operator' => '=', 'value' => null]],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Draft');
});

it('treats a null value with != as whereNotNull', function () {
    Post::factory()->for($this->user)->published()->create(['title' => 'Published']);
    Post::factory()->for($this->user)->create(['title' => 'Draft', 'published_at' => null]);

    $this->postJson('/posts/index', [
        'wheres' => [['column' => 'published_at', 'operator' => '!=', 'value' => null]],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Published');
});

it('filters on a belongsTo relation column', function () {
    $bob = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    Post::factory()->for($this->user)->create(['title' => 'By Ada']);
    Post::factory()->for($bob)->create(['title' => 'By Bob']);

    $this->postJson('/posts/index', [
        'wheres' => [['column' => 'user.name', 'operator' => '=', 'value' => 'Bob']],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'By Bob');
});

it('filters on a belongsToMany pivot column', function () {
    $tagged = Post::factory()->for($this->user)->create(['title' => 'Tagged']);
    $tagged->tags()->attach(Tag::create(['name' => 'news'])->id, ['label' => 'featured']);
    Post::factory()->for($this->user)->create(['title' => 'Untagged']);

    $this->postJson('/posts/index', [
        'wheres' => [['column' => 'tags.label', 'operator' => '=', 'value' => 'featured']],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Tagged');
});

it('throws when a where entry is missing its operator', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', [
        'wheres' => [['column' => 'title']],
    ])->assertServerError();
});
