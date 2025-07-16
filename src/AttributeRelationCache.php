<?php

namespace Weap\Junction;

class AttributeRelationCache
{
    /**
     * @var array
     */
    protected array $relations = [];

    /**
     * @param string $class
     * @param string $function
     * @param array $with
     * @return void
     */
    public function set(string $class, string $function, array $with): void
    {
        $this->relations[$class][$function] ??= $with;
    }

    /**
     * @param string $class
     * @param string $function
     * @return array|null
     */
    public function get(string $class, string $function): ?array
    {
        return $this->relations[$class][$function] ?? null;
    }
}
