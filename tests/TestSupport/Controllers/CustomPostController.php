<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Requests\PostFormRequest;
use Weap\Junction\Tests\TestSupport\Resources\PostJunctionResource;

/**
 * Mirrors PostController exactly, but pins a resource that writes its own
 * `toArray()` instead of leaning on the default rendering. Gives the conditional
 * field helpers (`whenAppended()`, `whenLoaded()`) a route to be exercised over,
 * since both answer to what the controller loaded onto the model.
 */
class CustomPostController extends Controller
{
    public string $model = Post::class;

    public string $formRequest = PostFormRequest::class;

    public string $resource = PostJunctionResource::class;

    /**
     * @return array<int|string, mixed>
     */
    public function relations(): array
    {
        return [
            'user',
            'comments' => fn (Relation $query) => $query->latest(),
            'comments.user',
            'tags',
            'user.posts',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['title', 'body', 'user.name', 'tags.label'];
    }
}
