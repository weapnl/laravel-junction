<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Comment;

class CommentController extends Controller
{
    public string $model = Comment::class;

    /**
     * @return array<int|string, mixed>
     */
    public function relations(): array
    {
        return ['post', 'user', 'post.user'];
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['body'];
    }
}
