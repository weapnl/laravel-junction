<?php

use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('paginates and exposes length-aware meta', function () {
    Post::factory()->count(5)->for($this->user)->create();

    $this->postJson('/posts/index', ['paginate' => 2])
        ->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('total', 5)
        ->assertJsonPath('page', 1)
        ->assertJsonPath('has_next_page', true);
});

it('returns the requested page', function () {
    Post::factory()->count(5)->for($this->user)->create();

    $this->postJson('/posts/index', ['paginate' => 2, 'page' => 3])
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('page', 3)
        ->assertJsonPath('has_next_page', false);
});

it('resolves the page that contains a given id', function () {
    $posts = Post::factory()->count(5)->for($this->user)->create();
    $last = $posts->last();

    $this->postJson('/posts/index', ['paginate' => 2, 'page_for_id' => $last->id])
        ->assertOk()
        ->assertJsonPath('page', 3)
        ->assertJsonPath('items.0.id', $last->id);
});

it('uses simple pagination without a total', function () {
    Post::factory()->count(5)->for($this->user)->create();

    $this->postJson('/posts/index', ['paginate' => 2, 'simple_pagination' => true])
        ->assertOk()
        ->assertJsonCount(2, 'items')
        ->assertJsonPath('total', null)
        ->assertJsonPath('has_next_page', true);
});

it('aborts when a controller forces simple pagination and it is not requested', function () {
    Post::factory()->for($this->user)->create();

    $this->getJson('/forced-posts')->assertStatus(400);
});

it('allows a forced-simple-pagination controller when simple pagination is requested', function () {
    Post::factory()->count(3)->for($this->user)->create();

    $this->postJson('/forced-posts/index', ['simple_pagination' => true, 'paginate' => 2])
        ->assertOk()
        ->assertJsonCount(2, 'items');
});

it('enforces an order by the model key when configured', function () {
    config()->set('junction.route.index.enforce_order_by_model_key', true);
    config()->set('junction.route.index.enforce_order_by_model_key_direction', 'desc');

    $posts = Post::factory()->count(3)->for($this->user)->create();

    $this->getJson('/posts')
        ->assertOk()
        ->assertJsonPath('items.0.id', $posts->last()->id)
        ->assertJsonPath('items.2.id', $posts->first()->id);
});
