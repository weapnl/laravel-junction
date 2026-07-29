<?php

use Illuminate\Http\Request;
use Weap\Junction\Http\Resources\JunctionResource;
use Weap\Junction\Tests\TestSupport\Models\Comment;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;
use Weap\Junction\Tests\TestSupport\Resources\PostJunctionResource;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
});

it('resolves plucked attributes straight from the container request', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    // Nothing is pushed in from a controller: the resource must read the pluck
    // tree from the request bound in the container by itself.
    $this->app->instance('request', Request::create('/', 'GET', [
        'pluck' => ['title'],
    ]));

    $data = (new PostJunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('id', $post->id)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('body')
        ->not->toHaveKey('excerpt')
        ->not->toHaveKey('author_name')
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});

it('resolves accessors from the container request', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);
    // `appends` is resolved by appending the accessor to the model, which a Junction
    // controller does before rendering. Outside one that is the caller's job, and the
    // key is the snake_case one the request normalizes to — `authorName` is sent in
    // camelCase below, but the accessor is keyed `author_name`.
    $post->append(['excerpt', 'author_name']);

    $this->app->instance('request', Request::create('/', 'GET', [
        'appends' => ['excerpt', 'authorName'],
        'pluck' => ['title'],
    ]));

    $data = (new PostJunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->toHaveKey('excerpt')
        ->toHaveKey('author_name', 'Ada')
        // `author_name` reads `$this->user?->name`, which loads the relation, so
        // `whenLoaded('user')` would answer with it. It was never requested through
        // `with`, so it stays out of the response.
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});

it('resolves relations and nested plucks from the container request', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);
    $post->load(['user', 'comments']);

    $this->app->instance('request', Request::create('/', 'GET', [
        'with' => ['user', 'comments'],
        'pluck' => ['title', 'user.name'],
    ]));

    $data = (new PostJunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->toHaveKey('user.name', 'Ada')
        ->not->toHaveKey('user.email')
        // `pluck` never reaches the `comments` level, so it imposes no restriction
        // there and every attribute is returned. This matches BaseResource.
        ->toHaveKey('comments.0.id')
        ->toHaveKey('comments.0.body');
});

it('cascades into relations of a resource without a toArray', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);
    $post->load(['user', 'comments']);

    $this->app->instance('request', Request::create('/', 'GET', [
        'with' => ['user', 'comments'],
        'pluck' => ['title', 'user.name', 'comments.body'],
    ]));

    $data = (new JunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('body');
    expect($data['user'])
        ->toHaveKey('name', 'Ada')
        ->not->toHaveKey('email');
    expect($data['comments'][0])
        ->toHaveKey('body')
        ->not->toHaveKey('post_id');
});

it('returns all attributes and no relations when nothing is requested', function () {
    $post = Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);
    $post->load('user');

    $this->app->instance('request', Request::create('/', 'GET'));

    $data = (new JunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->toHaveKey('body', 'World')
        // Loaded, but never requested through `with`.
        ->not->toHaveKey('user');
});

it('does not resolve accessors from the container request for default resource when not already appended', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);

    // `authorName` is sent in camelCase; the accessor is keyed `author_name`.
    $this->app->instance('request', Request::create('/', 'GET', [
        'appends' => ['excerpt', 'authorName'],
        'pluck' => ['title'],
    ]));

    $data = (new JunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('excerpt')
        ->not->toHaveKey('author_name')
        // `author_name` reads `$this->user?->name`, which loads the relation.
        // It was never requested through `with`, so it stays out of the response.
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});

it('does not resolve relations from the container request for default resource when not already loaded', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);

    $this->app->instance('request', Request::create('/', 'GET', [
        'with' => ['user', 'comments'],
        'pluck' => ['title', 'user.name'],
    ]));

    $data = (new JunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});

// TODO: remove temp tests
it('test custom append', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);
    $post->append(['author_name']);

    $this->app->instance('request', Request::create('/', 'GET', [
        // 'appends' => ['authorName'],
        'pluck' => ['title', 'user.name'],
    ]));

    $data = (new PostJunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('author_name')
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});

it('test non-custom append', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);
    $post->append(['author_name']);

    $this->app->instance('request', Request::create('/', 'GET', [
        // 'appends' => ['authorName'],
        'pluck' => ['title', 'user.name'],
    ]));

    $data = (new JunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('author_name')
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});

it('test custom relation', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);
    $post->load(['user']);

    $this->app->instance('request', Request::create('/', 'GET', [
        'pluck' => ['title', 'user.name'],
    ]));

    $data = (new PostJunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('author_name')
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});

it('test non-custom relation', function () {
    $post = Post::factory()->for($this->user)->has(Comment::factory()->count(2))->create(['title' => 'Hello']);
    $post->load(['user']);

    $this->app->instance('request', Request::create('/', 'GET', [
        'pluck' => ['title', 'user.name'],
    ]));

    $data = (new JunctionResource($post))->response()->getData(true);

    expect($data)
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('author_name')
        ->not->toHaveKey('user')
        ->not->toHaveKey('comments');
});
