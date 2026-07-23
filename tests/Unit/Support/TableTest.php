<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Weap\Junction\Support\Table;
use Weap\Junction\Tests\TestSupport\Models\Post;
use Weap\Junction\Tests\TestSupport\Models\User;

it('resolves a direct hasMany relation', function () {
    expect(Table::getRelation(Post::class, ['comments']))->toBeInstanceOf(HasMany::class);
});

it('resolves a belongsToMany relation', function () {
    expect(Table::getRelation(Post::class, ['tags']))->toBeInstanceOf(BelongsToMany::class);
});

it('resolves a nested relation to the leaf', function () {
    // Post -> comments (Comment) -> user (BelongsTo)
    expect(Table::getRelation(Post::class, ['comments', 'user']))->toBeInstanceOf(BelongsTo::class);
});

it('resolves a deeply nested relation', function () {
    // User -> posts (Post) -> comments (HasMany)
    expect(Table::getRelation(User::class, ['posts', 'comments']))->toBeInstanceOf(HasMany::class);
});
