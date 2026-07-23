<?php

use Illuminate\Support\Facades\Route;
use Weap\Junction\Tests\TestSupport\Controllers\MutatedSearchPostController;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\Tag;
use Weap\Junction\Tests\TestSupport\Models\User;

it('searches a direct column with search_value', function () {
    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::factory()->for($user)->create(['title' => 'Laravel Junction']);
    Post::factory()->for($user)->create(['title' => 'Something else']);

    $this->postJson('/posts/index', [
        'search_value' => 'Junction',
        'search_columns' => ['title'],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Laravel Junction');
});

it('falls back to the controller searchable columns', function () {
    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::factory()->for($user)->create(['title' => 'Findable', 'body' => 'nope']);
    Post::factory()->for($user)->create(['title' => 'nope', 'body' => 'Findable body']);

    // No search_columns given: Search falls back to searchable() = title, body, ...
    $this->postJson('/posts/index', [
        'search_value' => 'Findable',
    ])
        ->assertOk()
        ->assertJsonCount(2, 'items');
});

it('searches across a belongsTo relation column', function () {
    $ada = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $bob = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    Post::factory()->for($ada)->create(['title' => 'A']);
    Post::factory()->for($bob)->create(['title' => 'B']);

    $this->postJson('/posts/index', [
        'search_value' => 'Ada',
        'search_columns' => ['user.name'],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'A');
});

it('searches a belongsToMany pivot column', function () {
    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $tagged = Post::factory()->for($user)->create(['title' => 'Tagged']);
    $tagged->tags()->attach(Tag::create(['name' => 'news'])->id, ['label' => 'featured']);

    Post::factory()->for($user)->create(['title' => 'Untagged']);

    $this->postJson('/posts/index', [
        'search_value' => 'featured',
        'search_columns' => ['tags.label'],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'Tagged');
});

it('returns everything when the search value is empty', function () {
    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::factory()->count(2)->for($user)->create();

    $this->postJson('/posts/index', [
        'search_value' => '',
        'search_columns' => ['title'],
    ])
        ->assertOk()
        ->assertJsonCount(2, 'items');
});

it('applies mutateSearchValue before searching', function () {
    Route::junctionResource('mutated-posts', MutatedSearchPostController::class);

    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::factory()->for($user)->create(['title' => 'a mutated result']);
    Post::factory()->for($user)->create(['title' => 'untouched']);

    // The requested value never matches; the controller rewrites it to "mutated".
    $this->postJson('/mutated-posts/index', [
        'search_value' => 'zzz-no-match',
        'search_columns' => ['title'],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.title', 'a mutated result');
});
