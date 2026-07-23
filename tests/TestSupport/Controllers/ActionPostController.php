<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;

class ActionPostController extends Controller
{
    public string $model = Post::class;

    /**
     * No parameters => this action does not require a model id.
     *
     * @return array<string, int>
     */
    public function actionPublishAll(): array
    {
        $count = Post::query()->whereNull('published_at')->update(['published_at' => now()]);

        return ['published' => $count];
    }

    /**
     * Typed, non-nullable first parameter => this action requires a model id.
     *
     * @param Post $post
     */
    public function actionArchive(Post $post): Post
    {
        $post->forceFill(['title' => 'Archived: ' . $post->title])->save();

        return $post;
    }

    /**
     * Nullable first parameter => the model id is optional.
     *
     * @param ?Post $post
     * @return array<string, int|null>
     */
    public function actionTouch(?Post $post = null): array
    {
        return ['touched' => $post?->id];
    }
}
