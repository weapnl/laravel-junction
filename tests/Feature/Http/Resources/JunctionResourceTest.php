<?php

use Illuminate\Http\Request;
use Weap\Junction\Http\Resources\Exceptions\InvalidResourceException;
use Weap\Junction\Http\Resources\JunctionResource;
use Weap\Junction\Support\PluckedFields;
use Weap\Junction\Tests\TestSupport\Models\Comment;
use Weap\Junction\Tests\TestSupport\Models\MutatedPost;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\Tag;
use Weap\Junction\Tests\TestSupport\Models\User;
use Weap\Junction\Tests\TestSupport\Resources\PostJunctionResource;

beforeEach(function () {
    $this->user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $this->post = Post::factory()
        ->for($this->user)
        ->has(Comment::factory()->count(2)->for($this->user))
        ->create(['title' => 'Hello', 'body' => 'World']);

    $this->post->tags()->attach(Tag::factory()->create(['name' => 'php'])->id, ['label' => 'lang']);
});

it('returns every attribute when nothing is requested', function () {
    $this->postJson('/posts/index')
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonPath('items.0.body', 'World')
        ->assertJsonPath('items.0.user_id', $this->user->id);
});

it('restricts attributes to the plucked ones', function () {
    $this->postJson('/posts/index', ['pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonMissingPath('items.0.body')
        ->assertJsonMissingPath('items.0.user_id');
});

it('always returns the primary key', function () {
    $this->postJson('/posts/index', ['pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('items.0.id', $this->post->id);
});

it('keeps a requested accessor when pluck restricts the attributes', function () {
    // `pluck` governs attributes, `appends` governs accessors.
    $this->postJson('/posts/index', ['pluck' => ['title'], 'appends' => ['excerpt']])
        ->assertOk()
        ->assertJsonPath('items.0.excerpt', 'World')
        ->assertJsonMissingPath('items.0.body');
});

it('keeps a mutated column under pluck', function (string $field, string $expected) {
    // A mutator over an existing column is still an attribute: `pluck` governs it,
    // where an accessor without a column behind it belongs to `appends`.
    $post = MutatedPost::query()->findOrFail($this->post->id);

    $this->app->instance('request', Request::create('/', 'GET', ['pluck' => [$field]]));

    expect((new JunctionResource($post))->response()->getData(true))
        ->toHaveKey($field, $expected);
})->with([
    'old attribute notation' => ['title', 'HELLO'],
    'Attribute cast' => ['body', 'WORLD'],
]);

it('drops a mutated column that pluck leaves out', function () {
    $post = MutatedPost::query()->findOrFail($this->post->id);

    $this->app->instance('request', Request::create('/', 'GET', ['pluck' => ['title']]));

    expect((new JunctionResource($post))->response()->getData(true))
        ->toHaveKey('title', 'HELLO')
        ->not->toHaveKey('body');
});

it('returns the accessors in the model $appends', function () {
    // Tag declares `$appends = ['slug']`.
    $this->postJson('/tags/index')
        ->assertOk()
        ->assertJsonPath('items.0.slug', 'php');
});

it('omits an accessor in the model $appends that pluck left out', function () {
    // `pluck` is the client naming what it wants, so it wins over the model's own
    // `$appends`: a selection returns what it asked for and nothing besides.
    $this->postJson('/tags/index', ['pluck' => ['name']])
        ->assertOk()
        ->assertJsonPath('items.0.name', 'php')
        ->assertJsonMissingPath('items.0.slug');
});

it('keeps an accessor in the model $appends that was asked for', function (array $payload) {
    $this->postJson('/tags/index', $payload)
        ->assertOk()
        ->assertJsonPath('items.0.slug', 'php');
})->with([
    // The model resolved it either way, so naming it in `pluck` is enough; `appends`
    // is what resolves an accessor the model does not carry of its own accord.
    'named in pluck' => [['pluck' => ['name', 'slug']]],
    'named in appends' => [['pluck' => ['name'], 'appends' => ['slug']]],
]);

it('narrows the model $appends of a relation only where pluck reaches', function () {
    // Tag is the related model here, so the same rule applies one level down.
    $this->postJson('/posts/index', ['with' => ['tags'], 'pluck' => ['title', 'tags.name']])
        ->assertOk()
        ->assertJsonMissingPath('items.0.tags.0.slug');

    // `pluck` never reaches the tags, so it imposes nothing there.
    $this->postJson('/posts/index', ['with' => ['tags'], 'pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('items.0.tags.0.slug', 'php');
});

it('omits relations that were not requested', function () {
    $this->postJson('/posts/index')
        ->assertOk()
        ->assertJsonMissingPath('items.0.user')
        ->assertJsonMissingPath('items.0.comments');
});

it('returns a relation that was requested through with', function () {
    $this->postJson('/posts/index', ['with' => ['user']])
        ->assertOk()
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonPath('items.0.user.email', 'ada@example.com');
});

it('does not leak a relation that was eager loaded for an accessor', function () {
    // `author_name` is declared with `Junction::makeAttribute(with: ['user'])`,
    // so `user` is eager loaded even though the client never asked for it.
    $this->postJson('/posts/index', ['appends' => ['author_name']])
        ->assertOk()
        ->assertJsonPath('items.0.author_name', 'Ada')
        ->assertJsonMissingPath('items.0.user');
});

it('omits a loaded relation that was never requested, with no pluck given', function () {
    // A custom toArray renders `whenLoaded('user')`, which answers with the relation
    // now that it is loaded, so it is only kept out by the `with` gate. Nothing here
    // narrows the attributes.
    $post = Post::query()->with('user')->findOrFail($this->post->id);

    expect((new PostJunctionResource($post))->response()->getData(true))
        ->toHaveKey('title', 'Hello')
        ->not->toHaveKey('user');
});

it('applies pluck to a store response', function () {
    // A resource resolves the field selection from the request itself, so the write
    // routes honour it too.
    $this->postJson('/posts', [
        'title' => 'Created',
        'body' => 'Some body',
        'user_id' => $this->user->id,
        'pluck' => ['title'],
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Created')
        ->assertJsonMissingPath('body');
});

it('applies pluck to an update response', function () {
    $this->putJson('/posts/' . $this->post->id, ['title' => 'Updated', 'pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('title', 'Updated')
        ->assertJsonMissingPath('body');
});

it('applies pluck to a destroy response', function () {
    $this->deleteJson('/posts/' . $this->post->id, ['pluck' => ['title']])
        ->assertOk()
        ->assertJsonPath('title', 'Hello')
        ->assertJsonMissingPath('body');
});

it('does not load a relation for a write route on the default resource', function () {
    // Nothing is eager loaded for the write routes, and the default resource never
    // reaches for a relation itself. Answering with one takes a custom `toArray()`.
    $this->postJson('/posts', [
        'title' => 'Created',
        'user_id' => $this->user->id,
        'with' => ['user'],
        'appends' => ['excerpt'],
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Created')
        ->assertJsonMissingPath('user')
        ->assertJsonMissingPath('excerpt');
});

it('cascades pluck into a belongsTo relation', function () {
    $this->postJson('/posts/index', ['with' => ['user'], 'pluck' => ['title', 'user.name']])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonPath('items.0.user.id', $this->user->id)
        ->assertJsonMissingPath('items.0.user.email');
});

it('cascades pluck into a hasMany relation', function () {
    $response = $this->postJson('/posts/index', ['with' => ['comments'], 'pluck' => ['title', 'comments.body']])
        ->assertOk()
        ->assertJsonCount(2, 'items.0.comments')
        ->assertJsonMissingPath('items.0.comments.0.post_id')
        ->assertJsonMissingPath('items.0.comments.0.created_at');

    // The relation is ordered by `latest()`, so don't depend on which of the two
    // comments comes first.
    expect(array_column($response->json('items.0.comments'), 'body'))
        ->toEqualCanonicalizing($this->post->comments->pluck('body')->all());
});

it('cascades pluck into a belongsToMany relation', function () {
    $this->postJson('/posts/index', ['with' => ['tags'], 'pluck' => ['title', 'tags.name']])
        ->assertOk()
        ->assertJsonPath('items.0.tags.0.name', 'php')
        ->assertJsonMissingPath('items.0.tags.0.created_at');
});

it('cascades pluck two levels deep', function () {
    $this->postJson('/posts/index', [
        'with' => ['comments.user'],
        'pluck' => ['title', 'comments.body', 'comments.user.name'],
    ])
        ->assertOk()
        ->assertJsonPath('items.0.comments.0.user.name', 'Ada')
        ->assertJsonMissingPath('items.0.comments.0.user.email')
        ->assertJsonMissingPath('items.0.comments.0.post_id');
});

it('leaves a level pluck does not reach unrestricted', function () {
    $this->postJson('/posts/index', [
        'with' => ['user', 'comments'],
        'pluck' => ['title', 'user.name'],
    ])
        ->assertOk()
        ->assertJsonMissingPath('items.0.user.email')
        // `comments` carries no restriction, so it keeps every attribute.
        ->assertJsonPath('items.0.comments.0.post_id', $this->post->id);
});

it('returns an empty array for an empty hasMany relation', function () {
    $user = User::create(['name' => 'Grace', 'email' => 'grace@example.com']);

    $this->getJson('/users/' . $user->id . '?' . http_build_query(['with' => ['posts']]))
        ->assertOk()
        ->assertJsonPath('posts', []);
});

it('returns null for an empty belongsTo relation', function () {
    $this->post->setRelation('user', null);

    $resource = (new JunctionResource($this->post))->pluckedFields(
        new PluckedFields(relations: ['user' => true])
    );

    expect($resource->response()->getData(true))->toHaveKey('user', null);
});

it('cannot re-expose a hidden field through pluck', function () {
    $this->postJson('/posts/index', ['hidden_fields' => ['body'], 'pluck' => ['title', 'body']])
        ->assertOk()
        ->assertJsonPath('items.0.title', 'Hello')
        ->assertJsonMissingPath('items.0.body');
});

it('hides a relation field through hidden_fields', function () {
    $this->postJson('/posts/index', ['with' => ['user'], 'hidden_fields' => ['user.email']])
        ->assertOk()
        ->assertJsonPath('items.0.user.name', 'Ada')
        ->assertJsonMissingPath('items.0.user.email');
});

it('does not return the pivot of a belongsToMany relation', function () {
    $this->postJson('/posts/index', ['with' => ['tags']])
        ->assertOk()
        ->assertJsonPath('items.0.tags.0.name', 'php')
        ->assertJsonMissingPath('items.0.tags.0.pivot');
});

it('refuses to render something other than a model', function (mixed $resource, string $type) {
    // The field selection is resolved against the model: which keys are attributes,
    // which are accessors, which are relations. Nothing else can answer that.
    expect(fn () => (new JunctionResource($resource))->response())
        ->toThrow(InvalidResourceException::class, "Unable to render resource object for [{$type}]");
})->with([
    'associative array' => [['title' => 'Hello'], 'array'],
    'list' => [['Hello', 'World'], 'array'],
    'string' => ['Hello', 'string'],
    'plain object' => [new stdClass(), 'stdClass'],
]);

it('renders an empty resource for a null model', function () {
    // Laravel's own JsonResource answers an empty payload rather than failing.
    expect((new JunctionResource(null))->response()->getData(true))->toBe([]);
});

it('answers a store with a 200 rather than a 201', function () {
    $this->postJson('/posts', ['title' => 'Created', 'user_id' => $this->user->id])
        ->assertOk()
        ->assertJsonPath('title', 'Created');
});
