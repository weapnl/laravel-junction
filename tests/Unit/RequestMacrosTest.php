<?php

use Illuminate\Http\Request;

/** @param array<string, mixed> $params */
function makeJunctionRequest(array $params): Request
{
    return Request::create('/', 'GET', $params);
}

it('getPluckFields returns the pluck input', function () {
    expect(makeJunctionRequest(['pluck' => ['a', 'b']])->getPluckFields())->toBe(['a', 'b']);
});

it('getPluckFields is null when absent', function () {
    expect(makeJunctionRequest([])->getPluckFields())->toBeNull();
});

it('getAccessors returns the appends input', function () {
    expect(makeJunctionRequest(['appends' => ['x']])->getAccessors())->toBe(['x']);
});

it('getRelations returns with unchanged when there are no dotted appends', function () {
    expect(makeJunctionRequest(['with' => ['user']])->getRelations())->toBe(['user']);
});

it('getRelations injects the relation prefix implied by a dotted append', function () {
    expect(makeJunctionRequest(['appends' => ['comments.user']])->getRelations())->toBe(['comments']);
});

it('getRelations does not duplicate an already-present relation prefix', function () {
    expect(makeJunctionRequest(['with' => ['comments'], 'appends' => ['comments.user']])->getRelations())
        ->toBe(['comments']);
});

it('getRelations ignores non-dotted appends', function () {
    expect(makeJunctionRequest(['with' => ['user'], 'appends' => ['excerpt']])->getRelations())->toBe(['user']);
});

it('getRelations returns null when there is nothing to resolve', function () {
    expect(makeJunctionRequest([])->getRelations())->toBeNull();
});

it('getRelations skips injection on a startsWith prefix collision', function () {
    // 'commentsExtra' startsWith 'comments', so the guard suppresses a new relation.
    expect(makeJunctionRequest(['with' => ['commentsExtra'], 'appends' => ['comments.user']])->getRelations())
        ->toBe(['commentsExtra']);
});

it('normalizes attributes and accessors to snake_case', function () {
    expect(makeJunctionRequest(['pluck' => ['userId', 'user.emailVerifiedAt']])->getPluckFields())
        ->toBe(['user_id', 'user.email_verified_at'])
        ->and(makeJunctionRequest(['appends' => ['authorName']])->getAccessors())
        ->toBe(['author_name']);
});

it('normalizes relations to camelCase', function () {
    expect(makeJunctionRequest(['with' => ['user_posts', 'comments.user']])->getRelations())
        ->toBe(['userPosts', 'comments.user']);
});

it('keeps the relation segments of a field path camelCased', function () {
    // Every segment but the last is a relation, the last is an attribute.
    expect(makeJunctionRequest(['pluck' => ['user_posts.published_at']])->getPluckFields())
        ->toBe(['userPosts.published_at']);
});

it('normalizes a field selection given as a string', function () {
    expect(makeJunctionRequest(['pluck' => 'userId'])->getPluckFields())->toBe('user_id')
        ->and(makeJunctionRequest(['with' => 'user_posts'])->getRelations())->toBe('userPosts');
});
