<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\Tag;
use Weap\Junction\Tests\TestSupport\Models\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
});

it('appends a requested accessor', function () {
    $body = 'This is a fairly long body that will be truncated';
    Post::factory()->for($this->user)->create(['body' => $body]);

    $this->postJson('/posts/index', ['appends' => ['excerpt']])
        ->assertOk()
        ->assertJsonPath('items.0.excerpt', Str::limit($body, 20));
});

it('appends a nested accessor and eager loads the implied relation', function () {
    Post::factory()->for($this->user)->create();

    $this->postJson('/posts/index', ['appends' => ['user.initials']])
        ->assertOk()
        ->assertJsonPath('items.0.user.initials', 'AL');
});

it('eager loads the relations declared on a makeAttribute accessor', function () {
    Post::factory()->for($this->user)->create();

    // author_name is built with Junction::makeAttribute(..., with: ['user']), so
    // requesting it must auto-load the user relation. With lazy loading forbidden,
    // a failure to eager load would surface as a LazyLoadingViolationException (500).
    Model::preventLazyLoading();

    try {
        $this->postJson('/posts/index', ['appends' => ['author_name']])
            ->assertOk()
            ->assertJsonPath('items.0.author_name', 'Ada Lovelace');
    } finally {
        Model::preventLazyLoading(false);
    }
});

it('includes default appends for models using HasDefaultAppends', function () {
    Tag::create(['name' => 'Breaking News']);

    $this->getJson('/tags')
        ->assertOk()
        ->assertJsonPath('items.0.slug', 'breaking-news');
});
