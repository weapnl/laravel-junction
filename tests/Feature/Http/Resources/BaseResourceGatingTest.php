<?php

use Illuminate\Support\Str;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

/**
 * The `availableAttributes()`/`availableAccessors()`/`availableRelations()`
 * whitelists exist on the deprecated BaseResource only, so `/gated-posts` pins a
 * subclass of it. JunctionResource has no equivalent: a resource that wants to
 * gate its output writes its own `toArray()`.
 */
beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('only exposes attributes listed in availableAttributes', function () {
    Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    $this->getJson('/gated-posts')
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonMissingPath('items.0.body');
});

it('only exposes accessors listed in availableAccessors', function () {
    $body = 'This is a fairly long body';
    Post::factory()->for($this->user)->create(['body' => $body]);

    $this->postJson('/gated-posts/index', ['appends' => ['excerpt', 'author_name']])
        ->assertOk()
        ->assertJsonPath('items.0.excerpt', Str::limit($body, 20))
        ->assertJsonMissingPath('items.0.author_name');
});

it('only exposes relations listed in availableRelations', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/gated-posts/index', ['with' => ['user', 'comments']])
        ->assertOk()
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonMissingPath('items.0.comments');
});
