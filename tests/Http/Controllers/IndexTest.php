<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

it('returns all records wrapped in an items key', function () {
    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::factory()->count(3)->for($user)->create();

    $this->getJson('/posts')
        ->assertOk()
        ->assertJsonCount(3, 'items')
        ->assertJsonStructure(['items' => [['id', 'title', 'body']]]);
});

it('returns an empty items array when there are no records', function () {
    $this->getJson('/posts')
        ->assertOk()
        ->assertJsonCount(0, 'items')
        ->assertExactJson(['items' => []]);
});

it('does not add pagination meta when not paginating', function () {
    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::factory()->for($user)->create();

    $this->getJson('/posts')
        ->assertOk()
        ->assertJsonMissingPath('total')
        ->assertJsonMissingPath('page')
        ->assertJsonMissingPath('has_next_page');
});
