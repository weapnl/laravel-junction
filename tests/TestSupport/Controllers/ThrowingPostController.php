<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Requests\PostFormRequest;

class ThrowingPostController extends Controller
{
    public string $model = Post::class;

    public string $formRequest = PostFormRequest::class;

    /**
     * Throws after the model has already been saved, so a test can observe
     * whether the surrounding transaction rolled the row back.
     *
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     */
    public function afterStore(Model $model, array $validAttributes, array $invalidAttributes): Model
    {
        throw new RuntimeException('boom');
    }

    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     */
    public function afterUpdate(Model $model, array $validAttributes, array $invalidAttributes): Model
    {
        throw new RuntimeException('boom');
    }
}
