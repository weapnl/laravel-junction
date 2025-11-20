<?php

namespace Weap\Junction\Extensions;

use Closure;
use Illuminate\Routing\Controller;

class RelationExtension
{
    /**
     * @var array<Closure(array<string, Closure>|array<string>, Controller): array<string, Closure>|array<string>>
     */
    protected array $closures = [];

    /**
     * @param Closure(array<string, Closure>|array<string>, Controller): array<string, Closure>|array<string> $closure
     * @return static
     */
    public function add(Closure $closure): static
    {
        $this->closures[] = $closure;

        return $this;
    }

    /**
     * @return static
     */
    public function clear(): static
    {
        $this->closures = [];

        return $this;
    }

    /**
     * @param array<string, Closure>|array<string> $relations
     * @param Controller $controller
     * @return array<string, Closure>|array<string>
     */
    public function call(array $relations, Controller $controller): array
    {
        foreach ($this->closures as $closure) {
            $relations = $closure($relations, $controller) ?? $relations;
        }

        return $relations;
    }
}
