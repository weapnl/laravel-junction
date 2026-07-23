<?php

use Weap\Junction\AttributeRelationCache;

beforeEach(function () {
    $this->cache = new AttributeRelationCache();
});

it('returns null before anything is set', function () {
    expect($this->cache->get('App\\Foo', 'bar'))->toBeNull();
});

it('round-trips a stored relation list', function () {
    $this->cache->set('App\\Foo', 'bar', ['user']);

    expect($this->cache->get('App\\Foo', 'bar'))->toBe(['user']);
});

it('does not overwrite an existing entry (write-once)', function () {
    $this->cache->set('App\\Foo', 'bar', ['user']);
    $this->cache->set('App\\Foo', 'bar', ['comments']);

    expect($this->cache->get('App\\Foo', 'bar'))->toBe(['user']);
});

it('keeps entries for different functions independent', function () {
    $this->cache->set('App\\Foo', 'bar', ['user']);
    $this->cache->set('App\\Foo', 'baz', ['comments']);

    expect($this->cache->get('App\\Foo', 'bar'))->toBe(['user']);
    expect($this->cache->get('App\\Foo', 'baz'))->toBe(['comments']);
});

it('registers an empty array and will not later replace it', function () {
    $this->cache->set('App\\Foo', 'bar', []);
    $this->cache->set('App\\Foo', 'bar', ['user']);

    expect($this->cache->get('App\\Foo', 'bar'))->toBe([]);
});
