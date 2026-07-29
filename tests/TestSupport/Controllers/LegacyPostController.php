<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Resources\BaseResource;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Requests\PostFormRequest;

/**
 * Mirrors PostController exactly, but pins the deprecated BaseResource instead
 * of the JunctionResource default. Gives the backwards compatibility tests a
 * matched pair of routes over the same models.
 */
class LegacyPostController extends Controller
{
    public string $model = Post::class;

    public string $formRequest = PostFormRequest::class;

    public string $resource = BaseResource::class;

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
