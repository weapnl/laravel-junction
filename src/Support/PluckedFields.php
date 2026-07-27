<?php

namespace Weap\Junction\Support;

use Illuminate\Support\Arr;

final class PluckedFields
{
    /**
     * @param array<string, mixed>|null $attributes
     * @param array<string, mixed>|null $relations
     */
    public function __construct(
        protected ?array $attributes = null,
        protected ?array $relations = null,
    ) {
        //
    }

    /**
     * A selection that requests nothing: all attributes, no relations.
     */
    public static function none(): self
    {
        return new self();
    }

    /**
     * Build a single tree from a flat list of dot separated paths.
     *
     * @param array<int, string>|string|null $paths
     * @return array<string, mixed>|null
     */
    public static function tree(array|string|null $paths): ?array
    {
        if ($paths === null) {
            return null;
        }

        $paths = Arr::wrap($paths);

        // Sort shallowest-first, so a bare path (e.g. `contact`) can never clobber
        // a deeper one (e.g. `contact.name`) while the tree is being undotted.
        usort($paths, fn ($a, $b) => substr_count($a, '.') <=> substr_count($b, '.'));

        return Arr::undot(array_fill_keys($paths, true));
    }

    /**
     * Whether the given attribute should be returned. Unrestricted by default.
     *
     * @param string $key
     * @return bool
     */
    public function includesAttribute(string $key): bool
    {
        return $this->attributes === null || array_key_exists($key, $this->attributes);
    }

    /**
     * Whether the given relation was requested.
     *
     * @param string $key
     * @return bool
     */
    public function includesRelation(string $key): bool
    {
        return $this->relations !== null && array_key_exists($key, $this->relations);
    }

    /**
     * Descend one level, for the nested resource behind the given key.
     *
     * @param string $key
     * @return PluckedFields
     */
    public function nested(string $key): self
    {
        return new self(
            self::descend($this->attributes, $key),
            self::descend($this->relations, $key),
        );
    }

    /**
     * @param array<string, mixed>|null $tree
     * @param string $key
     * @return array<string, mixed>|null
     */
    protected static function descend(?array $tree, string $key): ?array
    {
        $nested = $tree[$key] ?? null;

        // A leaf (`true`) carries no restriction for the level below it.
        return is_array($nested) ? $nested : null;
    }
}
