<?php

use Weap\Junction\Support\PluckedFields;

it('builds a nested tree from dot separated paths', function () {
    expect(PluckedFields::tree(['title', 'user.name', 'user.company.id']))
        ->toBe([
            'title' => true,
            'user' => [
                'name' => true,
                'company' => ['id' => true],
            ],
        ]);
});

it('does not let a bare path clobber a deeper one', function () {
    // Whatever the input order, `contact` must not overwrite `contact.name`.
    expect(PluckedFields::tree(['contact', 'contact.name']))
        ->toBe(['contact' => ['name' => true]])
        ->and(PluckedFields::tree(['contact.name', 'contact']))
        ->toBe(['contact' => ['name' => true]]);
});

it('wraps a single path and passes null through', function () {
    expect(PluckedFields::tree('title'))->toBe(['title' => true])
        ->and(PluckedFields::tree(null))->toBeNull()
        ->and(PluckedFields::tree([]))->toBe([]);
});

it('leaves attributes unrestricted when no pluck is given', function () {
    expect(PluckedFields::none()->includesAttribute('anything'))->toBeTrue();
});

it('restricts attributes to the given tree', function () {
    $fields = new PluckedFields(attributes: ['title' => true]);

    expect($fields->includesAttribute('title'))->toBeTrue()
        ->and($fields->includesAttribute('body'))->toBeFalse();
});

it('requests no relations by default', function () {
    expect(PluckedFields::none()->includesRelation('user'))->toBeFalse();
});

it('reports the requested relations', function () {
    $fields = new PluckedFields(relations: ['user' => true]);

    expect($fields->includesRelation('user'))->toBeTrue()
        ->and($fields->includesRelation('comments'))->toBeFalse();
});

it('descends one level per tree', function () {
    $fields = new PluckedFields(
        attributes: ['title' => true, 'user' => ['name' => true]],
        relations: ['user' => ['posts' => true]],
    );

    $nested = $fields->nested('user');

    expect($nested->includesAttribute('name'))->toBeTrue()
        ->and($nested->includesAttribute('email'))->toBeFalse()
        ->and($nested->includesRelation('posts'))->toBeTrue();
});

it('leaves a level the tree does not reach unrestricted', function () {
    $fields = new PluckedFields(attributes: ['title' => true, 'user' => ['name' => true]]);

    // `comments` is absent from the tree, and `title` is a leaf: neither carries
    // a restriction for the level below it.
    expect($fields->nested('comments')->includesAttribute('anything'))->toBeTrue()
        ->and($fields->nested('title')->includesAttribute('anything'))->toBeTrue();
});
