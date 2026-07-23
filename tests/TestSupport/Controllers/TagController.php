<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Tag;

class TagController extends Controller
{
    public string $model = Tag::class;

    /**
     * @return array<int|string, mixed>
     */
    public function relations(): array
    {
        return ['posts'];
    }
}
