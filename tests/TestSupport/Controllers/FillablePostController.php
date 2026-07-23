<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;

class FillablePostController extends Controller
{
    public string $model = Post::class;

    public bool $saveFillable = true;
}
