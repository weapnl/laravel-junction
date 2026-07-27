<?php

use Illuminate\Support\Arr;
use Weap\Junction\Http\Resources\BaseResource;
use Weap\Junction\Http\Resources\JunctionResource;
use Weap\Junction\Tests\TestSupport\Models\AppendedPost;
use Weap\Junction\Tests\TestSupport\Models\Comment;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\Tag;
use Weap\Junction\Tests\TestSupport\Models\User;

/**
 * `Controller::$resource` defaults to JunctionResource, so an application that
 * never declared a resource class silently switches over. These tests run the
 * same request against `/posts` (JunctionResource) and `/legacy-posts`
 * (BaseResource) and assert the responses match, so the switch stays invisible.
 *
 * The documented exceptions have their own tests at the bottom of this file.
 */
beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $this->post = Post::factory()
        ->for($this->user)
        ->has(Comment::factory()->count(2)->for($this->user))
        ->create(['title' => 'Hello', 'body' => 'World']);

    $this->post->tags()->attach(Tag::factory()->create(['name' => 'php'])->id, ['label' => 'lang']);
});

/**
 * Sort keys recursively: both classes build their output in a different order,
 * which carries no meaning in JSON.
 *
 * @param array<mixed> $data
 * @return array<mixed>
 */
function sortedKeys(array $data): array
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = sortedKeys($value);
        }
    }

    ksort($data);

    return $data;
}

it('renders an index identically to BaseResource', function (array $payload) {
    $new = $this->postJson('/posts/index', $payload)->assertOk()->json();
    $old = $this->postJson('/legacy-posts/index', $payload)->assertOk()->json();

    expect(sortedKeys($new))->toEqual(sortedKeys($old));
})->with([
    'no parameters' => [[]],
    'pluck' => [['pluck' => ['title']]],
    'empty pluck' => [['pluck' => []]],
    'with' => [['with' => ['user']]],
    'with a hasMany' => [['with' => ['comments']]],
    'with a belongsToMany' => [['with' => ['tags']]],
    'with and pluck' => [['with' => ['user'], 'pluck' => ['title', 'user.name']]],
    'nested with and pluck' => [['with' => ['comments.user'], 'pluck' => ['title', 'comments.user.name']]],
    'a level pluck does not reach stays unrestricted' => [['with' => ['user', 'comments'], 'pluck' => ['title', 'user.name']]],
    'appends' => [['appends' => ['excerpt']]],
    'appends and pluck' => [['appends' => ['excerpt'], 'pluck' => ['title']]],
    'camelCase pluck' => [['pluck' => ['userId']]],
    'camelCase appends' => [['appends' => ['authorName']]],
    'camelCase nested pluck' => [['with' => ['user'], 'pluck' => ['user.emailVerifiedAt']]],
    'an accessor that eager loads a relation' => [['appends' => ['author_name']]],
    'hidden_fields' => [['hidden_fields' => ['body']]],
    'hidden_fields on a relation' => [['with' => ['user'], 'hidden_fields' => ['user.email']]],
    'count' => [['count' => ['comments']]],
    'count and pluck' => [['count' => ['comments'], 'pluck' => ['title']]],
    'pagination' => [['paginate' => 1, 'page' => 1]],
    'simple pagination' => [['paginate' => 1, 'simple_pagination' => true]],
    'order and limit' => [['orders' => [['column' => 'title', 'direction' => 'desc']], 'limit' => 5]],
]);

it('renders a show identically to BaseResource', function (array $payload) {
    $new = $this->getJson('/posts/' . $this->post->id . '?' . http_build_query($payload))->assertOk()->json();
    $old = $this->getJson('/legacy-posts/' . $this->post->id . '?' . http_build_query($payload))->assertOk()->json();

    expect(sortedKeys($new))->toEqual(sortedKeys($old));
})->with([
    'no parameters' => [[]],
    'pluck' => [['pluck' => ['title']]],
    'with and pluck' => [['with' => ['user'], 'pluck' => ['title', 'user.name']]],
    'appends' => [['appends' => ['excerpt']]],
]);

/*
 * The documented differences. See the "Unreleased" section of CHANGELOG.md.
 */

it('no longer exposes a hidden field through pluck', function () {
    $payload = ['hidden_fields' => ['body'], 'pluck' => ['title', 'body']];

    $this->postJson('/legacy-posts/index', $payload)
        ->assertOk()
        ->assertJsonPath('items.0.body', 'World');

    $this->postJson('/posts/index', $payload)
        ->assertOk()
        ->assertJsonMissingPath('items.0.body');
});

it('no longer resolves an accessor that was only plucked', function () {
    // `excerpt` is an accessor, so it belongs to `appends`, not to `pluck`.
    $this->postJson('/legacy-posts/index', ['pluck' => ['excerpt']])
        ->assertOk()
        ->assertJsonPath('items.0.excerpt', 'World');

    $this->postJson('/posts/index', ['pluck' => ['excerpt']])
        ->assertOk()
        ->assertJsonMissingPath('items.0.excerpt');
});

it('no longer returns a null value for a field that does not exist', function () {
    // BaseResource resolved every plucked key through `Model::only()`, which
    // answers null for anything it does not know, letting a client add arbitrary
    // keys to the response.
    $this->postJson('/legacy-posts/index', ['pluck' => ['nope']])
        ->assertOk()
        ->assertJsonPath('items.0.nope', null);

    $this->postJson('/posts/index', ['pluck' => ['nope']])
        ->assertOk()
        ->assertJsonMissingPath('items.0.nope');
});

it('answers a store with a 200 on both resources', function () {
    // Both used to go through `response()->json()`. Returning a resource instead
    // would make Laravel answer a recently created model with a 201.
    $payload = ['title' => 'Created', 'user_id' => $this->user->id];

    $this->postJson('/posts', $payload)->assertOk();
    $this->postJson('/legacy-posts', $payload)->assertOk();
});

/**
 * The two routes necessarily act on different records, so the key cannot match.
 *
 * @param array<mixed> $data
 * @return array<mixed>
 */
function shape(array $data): array
{
    return sortedKeys(Arr::except($data, 'id'));
}

it('renders a store identically to BaseResource', function (array $payload) {
    $payload = ['title' => 'Created', 'body' => 'Some body', 'user_id' => $this->user->id, ...$payload];

    $new = $this->postJson('/posts', $payload)->assertOk()->json();
    $old = $this->postJson('/legacy-posts', $payload)->assertOk()->json();

    // Both answer with the bare model, unwrapped.
    expect(shape($new))->toEqual(shape($old))
        ->and($new)->not->toHaveKey('data');
})->with([
    'no parameters' => [[]],
    // Nothing is eager loaded for a write route and neither resource reaches for a
    // relation or an accessor itself, so these two change nothing on either side.
    'appends' => [['appends' => ['excerpt']]],
    'with' => [['with' => ['user']]],
]);

it('renders an update identically to BaseResource', function () {
    $legacy = Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    $new = $this->putJson('/posts/' . $this->post->id, ['title' => 'Updated'])->assertOk()->json();
    $old = $this->putJson('/legacy-posts/' . $legacy->id, ['title' => 'Updated'])->assertOk()->json();

    expect(shape($new))->toEqual(shape($old))
        ->and($new)->not->toHaveKey('data');
});

it('renders a destroy identically to BaseResource', function () {
    $legacy = Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    $new = $this->deleteJson('/posts/' . $this->post->id)->assertOk()->json();
    $old = $this->deleteJson('/legacy-posts/' . $legacy->id)->assertOk()->json();

    expect(shape($new))->toEqual(shape($old))
        ->and($new)->not->toHaveKey('data');
});

it('now applies pluck to the write routes, where BaseResource ignored it', function () {
    // BaseResource only ever received a field selection from the index and show
    // routes; a JunctionResource reads it from the request, on every route.
    $legacy = Post::factory()->for($this->user)->create(['title' => 'Hello', 'body' => 'World']);

    $this->putJson('/legacy-posts/' . $legacy->id, ['title' => 'Updated', 'pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('body', 'World');

    $this->putJson('/posts/' . $this->post->id, ['title' => 'Updated', 'pluck' => ['title']])
        ->assertOk()
        ->assertJsonMissingPath('body');
});

it('honours the model $appends without the HasDefaultAppends trait', function () {
    $post = AppendedPost::query()->findOrFail($this->post->id);

    expect((new JunctionResource($post))->response()->getData(true))
        ->toHaveKey('excerpt', 'World');

    expect((new BaseResource($post))->toArray(request()))
        ->not->toHaveKey('excerpt');
});
