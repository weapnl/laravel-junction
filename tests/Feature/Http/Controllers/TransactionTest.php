<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('rolls back a failed store when store transactions are enabled', function () {
    config()->set('junction.use_db_transactions.store', true);

    $this->postJson('/throwing-posts', ['title' => 'Rollback', 'user_id' => $this->user->id])
        ->assertServerError();

    $this->assertDatabaseCount('posts', 0);
});

it('persists a failed store when store transactions are disabled', function () {
    config()->set('junction.use_db_transactions.store', false);

    $this->postJson('/throwing-posts', ['title' => 'Persisted', 'user_id' => $this->user->id])
        ->assertServerError();

    // No transaction: the row was already committed before the hook threw.
    $this->assertDatabaseHas('posts', ['title' => 'Persisted']);
});

it('rolls back a failed update when update transactions are enabled', function () {
    config()->set('junction.use_db_transactions.update', true);

    $post = Post::factory()->for($this->user)->create(['title' => 'Original']);

    $this->putJson("/throwing-posts/{$post->id}", ['title' => 'Changed'])
        ->assertServerError();

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Original']);
});
