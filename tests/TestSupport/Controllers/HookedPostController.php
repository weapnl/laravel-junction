<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Illuminate\Database\Eloquent\Model;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Requests\PostFormRequest;

class HookedPostController extends Controller
{
    public string $model = Post::class;

    public string $formRequest = PostFormRequest::class;

    /**
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     * @return array<string, mixed>
     */
    public function beforeStore(array $validAttributes, array $invalidAttributes): array
    {
        $validAttributes['title'] = strtoupper((string) $validAttributes['title']);

        return $validAttributes;
    }

    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     */
    public function afterStore(Model $model, array $validAttributes, array $invalidAttributes): Model
    {
        $model->forceFill(['body' => 'after-store-hook'])->save();

        return $model;
    }

    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     * @return array<string, mixed>
     */
    public function beforeUpdate(Model $model, array $validAttributes, array $invalidAttributes): array
    {
        $validAttributes['title'] = strtoupper((string) ($validAttributes['title'] ?? $model->title));

        return $validAttributes;
    }

    /**
     * @param Model $model
     * @param array<string, mixed> $validAttributes
     * @param array<string, mixed> $invalidAttributes
     */
    public function afterUpdate(Model $model, array $validAttributes, array $invalidAttributes): Model
    {
        $model->forceFill(['body' => 'after-update-hook'])->save();

        return $model;
    }
}
