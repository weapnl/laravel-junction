<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;

class ForcedPaginationPostController extends Controller
{
    public string $model = Post::class;

    public bool $forceSimplePagination = true;
}
