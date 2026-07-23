<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Resources\GatedPostResource;

class GatedPostController extends Controller
{
    public string $model = Post::class;

    public string $resource = GatedPostResource::class;

    /**
     * @return array<int|string, mixed>
     */
    public function relations(): array
    {
        return ['user', 'comments'];
    }
}
