<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\User;

class UserController extends Controller
{
    public string $model = User::class;

    /**
     * @return array<int|string, mixed>
     */
    public function relations(): array
    {
        return ['posts', 'posts.comments', 'comments'];
    }

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['name', 'email'];
    }
}
