<?php

use Weap\Junction\Extensions\RelationExtension;
use Weap\Junction\Tests\TestSupport\Controllers\PostController;

beforeEach(function () {
    $this->extension = new RelationExtension();
    $this->controller = new PostController();
});

it('returns relations unchanged when no closures are registered', function () {
    expect($this->extension->call(['user'], $this->controller))->toBe(['user']);
});

it('runs closures in order, each seeing the previous output', function () {
    $this->extension
        ->add(fn (array $relations) => [...$relations, 'a'])
        ->add(fn (array $relations) => [...$relations, 'b']);

    expect($this->extension->call(['user'], $this->controller))->toBe(['user', 'a', 'b']);
});

it('treats a null return as passthrough', function () {
    $this->extension->add(fn (array $relations) => null);

    expect($this->extension->call(['user'], $this->controller))->toBe(['user']);
});

it('passes the controller to each closure', function () {
    $seen = null;
    $this->extension->add(function (array $relations, $controller) use (&$seen) {
        $seen = $controller;

        return $relations;
    });

    $this->extension->call([], $this->controller);

    expect($seen)->toBe($this->controller);
});

it('clears registered closures', function () {
    $this->extension->add(fn (array $relations) => [...$relations, 'x']);
    $this->extension->clear();

    expect($this->extension->call(['user'], $this->controller))->toBe(['user']);
});
