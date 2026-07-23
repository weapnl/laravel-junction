<?php

use Illuminate\Support\Facades\Gate;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $this->actingAs($this->user);
    $this->post = Post::factory()->for($this->user)->create(['title' => 'Old']);
});

// When usePolicy is true and no ability is granted, Gate denies by default (403).

it('forbids index without the viewAny ability', function () {
    $this->getJson('/policy-posts')->assertForbidden();
});

it('forbids show without the view ability', function () {
    $this->getJson("/policy-posts/{$this->post->id}")->assertForbidden();
});

it('forbids store without the create ability', function () {
    $this->postJson('/policy-posts', ['title' => 'New'])->assertForbidden();
});

it('forbids update without the update ability', function () {
    $this->putJson("/policy-posts/{$this->post->id}", ['title' => 'New'])->assertForbidden();
});

it('forbids destroy without the delete ability', function () {
    $this->deleteJson("/policy-posts/{$this->post->id}")->assertForbidden();
});

it('forbids action without the action ability', function () {
    $this->putJson('/policy-posts', ['action' => 'ping'])->assertForbidden();
});

it('allows index when the viewAny ability is granted', function () {
    Gate::define('viewAny', fn () => true);

    $this->getJson('/policy-posts')->assertOk();
});

it('allows store when the create ability is granted', function () {
    Gate::define('create', fn () => true);

    $this->postJson('/policy-posts', ['title' => 'New', 'user_id' => $this->user->id])
        ->assertOk()
        ->assertJsonPath('title', 'New');
});

it('allows action when the action ability is granted', function () {
    Gate::define('action', fn () => true);

    $this->putJson('/policy-posts', ['action' => 'ping'])
        ->assertOk()
        ->assertExactJson(['pong' => true]);
});
