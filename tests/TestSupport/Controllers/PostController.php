<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Requests\PostFormRequest;

class PostController extends Controller
{
    public string $model = Post::class;

    public string $formRequest = PostFormRequest::class;

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
