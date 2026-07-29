<?php

namespace Weap\Junction\Support;

use Illuminate\Support\Str;

/**
 * Normalizes the dot separated paths to the correct casing.
 */
class FieldPath
{
    /**
     * Normalize paths that end in an attribute or accessor.
     *
     * @param array<int, mixed>|string|null $paths
     * @return array<int, mixed>|string|null
     */
    public static function fields(array|string|null $paths): array|string|null
    {
        return static::normalize($paths, function (string $path): string {
            $segments = explode('.', $path);
            $field = Str::snake((string) array_pop($segments));

            return implode('.', [...array_map(fn ($relation) => Str::camel($relation), $segments), $field]);
        });
    }

    /**
     * Normalize paths that consist of relations only.
     *
     * @param array<int, mixed>|string|null $paths
     * @return array<int, mixed>|string|null
     */
    public static function relations(array|string|null $paths): array|string|null
    {
        return static::normalize($paths, fn (string $path): string => implode('.', array_map(
            fn ($relation) => Str::camel($relation),
            explode('.', $path),
        )));
    }

    /**
     * Apply the given normalizer, keeping the shape of the input intact.
     *
     * @param array<int, mixed>|string|null $paths
     * @param callable(string): string $normalizer
     * @return array<int, mixed>|string|null
     */
    protected static function normalize(array|string|null $paths, callable $normalizer): array|string|null
    {
        if ($paths === null) {
            return null;
        }

        if (is_string($paths)) {
            return $normalizer($paths);
        }

        return array_map(fn ($path) => is_string($path) ? $normalizer($path) : $path, $paths);
    }
}
