<?php

namespace Weap\Junction\Tests\TestSupport\Controllers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Tests\TestSupport\Models\Post;

class MutatedSearchPostController extends Controller
{
    public string $model = Post::class;

    /**
     * @return array<int, string>
     */
    public function searchable(): array
    {
        return ['title'];
    }

    public function mutateSearchValue(string $searchValue): string
    {
        return 'mutated';
    }
}
