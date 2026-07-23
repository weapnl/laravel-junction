<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('stores a record from validated attributes and returns it', function () {
    $this->postJson('/posts', [
        'title' => 'Hello',
        'body' => 'World',
        'user_id' => $this->user->id,
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Hello')
        ->assertJsonPath('body', 'World');

    $this->assertDatabaseHas('posts', ['title' => 'Hello', 'body' => 'World']);
});

it('does not persist attributes that are not validated', function () {
    // "extra" is not part of PostFormRequest rules, so it must not be saved.
    $this->postJson('/posts', [
        'title' => 'Hello',
        'user_id' => $this->user->id,
        'extra' => 'should-be-ignored',
    ])->assertOk();

    expect(Post::first()->getAttributes())->not->toHaveKey('extra');
});

it('rejects a store that fails validation', function () {
    $this->postJson('/posts', ['body' => 'no title'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');

    $this->assertDatabaseCount('posts', 0);
});

it('saves fillable attributes when saveFillable is enabled', function () {
    // FillablePostController uses the default form request (no rules) + saveFillable,
    // so attributes come straight from the fillable set with no validation.
    $this->postJson('/fillable-posts', [
        'title' => 'Filled',
        'body' => 'Body',
        'user_id' => $this->user->id,
    ])->assertOk();

    $this->assertDatabaseHas('posts', ['title' => 'Filled', 'body' => 'Body']);
});

it('runs the before and after store hooks', function () {
    $this->postJson('/hooked-posts', [
        'title' => 'lowercase',
        'user_id' => $this->user->id,
    ])
        ->assertOk()
        ->assertJsonPath('title', 'LOWERCASE')
        ->assertJsonPath('body', 'after-store-hook');

    $this->assertDatabaseHas('posts', ['title' => 'LOWERCASE', 'body' => 'after-store-hook']);
});
