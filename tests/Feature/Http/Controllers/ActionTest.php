<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('runs a model-less action and returns its raw value', function () {
    Post::factory()->count(3)->for($this->user)->create(['published_at' => null]);

    $this->putJson('/action-posts', ['action' => 'publishAll'])
        ->assertOk()
        ->assertExactJson(['published' => 3]);

    expect(Post::whereNull('published_at')->count())->toBe(0);
});

it('requires an id for an action with a non-nullable model parameter', function () {
    Post::factory()->for($this->user)->create();

    $this->putJson('/action-posts', ['action' => 'archive'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('id');
});

it('runs a model-bound action when a valid id is given', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Draft']);

    $this->putJson('/action-posts', ['action' => 'archive', 'id' => $post->id])
        ->assertOk()
        ->assertJsonPath('title', 'Archived: Draft');
});

it('returns 404 when a model-bound action gets an unknown id', function () {
    $this->putJson('/action-posts', ['action' => 'archive', 'id' => 999])
        ->assertNotFound();
});

it('runs a nullable-model action with no id', function () {
    $this->putJson('/action-posts', ['action' => 'touch'])
        ->assertOk()
        ->assertExactJson(['touched' => null]);
});

it('runs a nullable-model action with an id', function () {
    $post = Post::factory()->for($this->user)->create();

    $this->putJson('/action-posts', ['action' => 'touch', 'id' => $post->id])
        ->assertOk()
        ->assertExactJson(['touched' => $post->id]);
});

it('rejects an unknown action name', function () {
    $this->putJson('/action-posts', ['action' => 'doesNotExist'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('action');
});

it('rejects a request with no action', function () {
    $this->putJson('/action-posts', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('action');
});
