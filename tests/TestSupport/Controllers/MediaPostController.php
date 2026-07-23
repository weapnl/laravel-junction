<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\MediaPost;
use Weap\Junction\Tests\TestSupport\Requests\MediaPostFormRequest;

class MediaPostController extends Controller
{
    public string $model = MediaPost::class;

    public string $formRequest = MediaPostFormRequest::class;
}
