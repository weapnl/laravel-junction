<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Requests\PostFormRequest;

class PolicyPostController extends Controller
{
    public string $model = Post::class;

    public string $formRequest = PostFormRequest::class;

    public bool $usePolicy = true;

    /**
     * @param ?Post $post
     * @return array<string, bool>
     */
    public function actionPing(?Post $post = null): array
    {
        return ['pong' => true];
    }
}
