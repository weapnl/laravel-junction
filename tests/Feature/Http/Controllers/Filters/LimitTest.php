<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

it('limits the number of returned records', function () {
    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::factory()->count(5)->for($user)->create();

    $this->postJson('/posts/index', [
        'limit' => 2,
    ])
        ->assertOk()
        ->assertJsonCount(2, 'items');
});
