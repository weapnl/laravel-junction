<?php

use Weap\Junction\Tests\TestSupport\Models\Comment;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\Tag;
use Weap\Junction\Tests\TestSupport\Models\User;
use Weap\Junction\Tests\TestSupport\Resources\PostJunctionResource;

/**
 * A resource that writes its own `toArray()` guards accessors and relations with
 * Laravel's own `whenAppended()` and `whenLoaded()`. Both answer to the model
 * rather than to the request: Junction resolves `appends` by appending the accessor
 * to the model and `with` by eager loading the relation, so the standard helpers
 * pick them up without the resource having to look at the request at all.
 *
 * `/custom-posts` is PostController with PostJunctionResource pinned to it.
 */
beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $this->post = Post::factory()
        ->for($this->user)
        ->has(Comment::factory()->count(2)->for($this->user))
        ->create(['title' => 'Hello', 'body' => 'World']);

    $this->post->tags()->attach(Tag::factory()->create(['name' => 'php'])->id, ['label' => 'lang']);
});

it('omits an accessor that was not appended', function () {
    $this->postJson('/custom-posts/index')
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonMissingPath('items.0.excerpt')
        ->assertJsonMissingPath('items.0.author_name');
});

it('returns an accessor that appends requested', function () {
    $this->postJson('/custom-posts/index', ['appends' => ['excerpt']])
        ->assertOk()
        ->assertJsonPath('items.0.excerpt', 'World')
        ->assertJsonMissingPath('items.0.author_name');
});

it('keeps an appended accessor when pluck restricts the attributes', function () {
    $this->postJson('/custom-posts/index', ['appends' => ['excerpt'], 'pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonPath('items.0.excerpt', 'World')
        ->assertJsonMissingPath('items.0.body');
});

it('omits a relation that was not loaded', function () {
    $this->postJson('/custom-posts/index')
        ->assertOk()
        ->assertJsonMissingPath('items.0.user')
        ->assertJsonMissingPath('items.0.comments');
});

it('returns a relation that with requested', function () {
    $this->postJson('/custom-posts/index', ['with' => ['user', 'comments']])
        ->assertOk()
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonCount(2, 'items.0.comments');
});

it('does not leak a relation that was eager loaded for an accessor', function () {
    // `author_name` is declared with `Junction::makeAttribute(with: ['user'])`, so
    // `user` is eager loaded and `whenLoaded('user')` would answer with it. The
    // client never asked for it through `with`, so it stays out of the response.
    $this->postJson('/custom-posts/index', ['appends' => ['author_name']])
        ->assertOk()
        ->assertJsonPath('items.0.author_name', 'Ada')
        ->assertJsonMissingPath('items.0.user');
});

it('cascades the field selection into a guarded relation', function () {
    $this->postJson('/custom-posts/index', [
        'with' => ['user', 'comments'],
        'pluck' => ['title', 'user.name'],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonMissingPath('items.0.user.email')
        // `pluck` never reaches the comments, so they carry no restriction.
        ->assertJsonPath('items.0.comments.0.post_id', $this->post->id);
});

it('cascades the field selection two levels deep', function () {
    $this->postJson('/custom-posts/index', [
        'with' => ['comments.user'],
        'pluck' => ['title', 'comments.body', 'comments.user.name'],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.comments.0.user.name', 'Ada')
        ->assertJsonMissingPath('items.0.comments.0.user.email')
        ->assertJsonMissingPath('items.0.comments.0.post_id');
});

it('answers a write route with the model alone', function () {
    // Nothing is eager loaded or appended for the write routes, so the guarded
    // fields stay out however the request asks for them.
    $this->postJson('/custom-posts', [
        'title' => 'Created',
        'user_id' => $this->user->id,
        'appends' => ['excerpt'],
        'with' => ['user'],
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Created')
        ->assertJsonMissingPath('excerpt')
        ->assertJsonMissingPath('user');
});

it('resolves the field selection without a controller', function () {
    // Read straight from the container request. `with` needs the model to carry the
    // relation, which is the caller's job here.
    $post = Post::query()->with('user')->findOrFail($this->post->id);

    app()->instance('request', Illuminate\Http\Request::create('/', 'GET', [
        'with' => ['user'],
        'pluck' => ['title', 'user.name'],
    ]));

    expect((new PostJunctionResource($post))->response()->getData(true))
        ->toHaveKey('title', 'Hello')
        ->toHaveKey('user.name', 'Ada')
        ->not->toHaveKey('user.email')
        ->not->toHaveKey('body');
});
