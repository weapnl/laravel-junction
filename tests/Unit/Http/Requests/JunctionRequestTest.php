<?php

use Illuminate\Http\Request;
use Weap\Junction\Http\Requests\JunctionRequest;

/**
 * @param array<string, mixed> $params
 * @return JunctionRequest
 */
function junctionRequest(array $params): JunctionRequest
{
    return JunctionRequest::createFrom(Request::create('/', 'GET', $params));
}

it('reads the selection off the request', function () {
    $fields = junctionRequest([
        'pluck' => ['title', 'user.name'],
        'with' => ['user'],
    ])->pluckedFields();

    expect($fields->includesAttribute('title'))->toBeTrue()
        ->and($fields->includesAttribute('body'))->toBeFalse()
        ->and($fields->includesRelation('user'))->toBeTrue()
        ->and($fields->nested('user')->includesAttribute('name'))->toBeTrue()
        ->and($fields->nested('user')->includesAttribute('email'))->toBeFalse();
});

it('adds the accessors appends asked for to the selection', function () {
    // Asking for an accessor is also asking for it to be returned, so `appends`
    // widens the selection `pluck` narrowed, at every level.
    $fields = junctionRequest([
        'pluck' => ['title', 'user.name'],
        'appends' => ['excerpt', 'user.initials'],
    ])->pluckedFields();

    expect($fields->includesAttribute('excerpt'))->toBeTrue()
        ->and($fields->includesAttribute('body'))->toBeFalse()
        ->and($fields->nested('user')->includesAttribute('initials'))->toBeTrue()
        ->and($fields->nested('user')->includesAttribute('email'))->toBeFalse();
});

it('does not let appends narrow the selection on its own', function () {
    // Without a `pluck` there is no restriction to widen: everything is returned,
    // rather than only the accessor that was named.
    $fields = junctionRequest(['appends' => ['excerpt']])->pluckedFields();

    expect($fields->includesAttribute('excerpt'))->toBeTrue()
        ->and($fields->includesAttribute('body'))->toBeTrue();
});

it('memoizes the field selection per request', function () {
    $request = junctionRequest(['pluck' => ['title']]);

    expect($request->pluckedFields())->toBe($request->pluckedFields());
});
