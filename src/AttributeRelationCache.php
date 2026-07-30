<?php

namespace Weap\Junction;

class AttributeRelationCache
{
    /**
     * @var array<string, array<string, array<int|string, mixed>>>
     */
    protected array $relations = [];

    /**
     * @param string $class
     * @param string $function
     * @param array<int|string, mixed> $with
     * @return void
     */
    public function set(string $class, string $function, array $with): void
    {
        $this->relations[$class][$function] ??= $with;
    }

    /**
     * @param string $class
     * @param string $function
     * @return array<int|string, mixed>|null
     */
    public function get(string $class, string $function): ?array
    {
        return $this->relations[$class][$function] ?? null;
    }
}
