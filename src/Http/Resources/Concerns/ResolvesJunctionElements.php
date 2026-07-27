<?php

namespace Weap\Junction\Http\Resources\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JsonSerializable;
use Weap\Junction\Http\Resources\AnonymousResourceCollection;
use Weap\Junction\Http\Resources\Exceptions\InvalidResourceException;
use Weap\Junction\Http\Resources\JunctionResource;
use Weap\Junction\Support\PluckedFields;

trait ResolvesJunctionElements
{
    /**
     * The fields plucked for this resource. Null until resolved, either from
     * the request or by the resource that contains this one.
     */
    protected ?PluckedFields $pluckedFields = null;

    /**
     * Set the fields plucked for this resource. Called by the containing
     * resource, which knows its own subtree, so the nested resource does not
     * have to resolve anything from the request itself.
     *
     * @param PluckedFields $fields
     * @return static
     */
    public function pluckedFields(PluckedFields $fields): static
    {
        $this->pluckedFields = $fields;

        return $this;
    }

    /**
     * The fields plucked for this resource, falling back to the request.
     *
     * @param Request $request
     * @return PluckedFields
     */
    protected function resolvePluckedFields(Request $request): PluckedFields
    {
        return $this->pluckedFields ??= $this->resolveJunctionRequestFrom($request)->pluckedFields();
    }

    /**
     * Resolves `data` for the resource.
     *
     * @param Request $request
     * @return array<string, mixed>
     *
     * @throws InvalidResourceException
     */
    protected function resolveResourceObject(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        if (! $this->resource instanceof Model) {
            throw InvalidResourceException::attemptingToRenderNonModel($this->resource);
        }

        return [
            ...$this->resolveResourceIdentifier(),
            ...$this->resolveResourceAttributes($request),
        ];
    }

    /**
     * Resolve the resource's identifier, which is always returned.
     *
     * @return array<string, mixed>
     */
    public function resolveResourceIdentifier(): array
    {
        if (! $this->resource instanceof Model) {
            return [];
        }

        return [$this->resource->getKeyName() => $this->resource->getKey()];
    }

    /**
     * Resolve the resource's attributes, accessors and relations.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function resolveResourceAttributes(Request $request): array
    {
        $fields = $this->resolvePluckedFields($request);

        $data = $this->toAttributes($request);

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        $data = (new Collection($data))
            ->mapWithKeys(
                fn ($value, $key) => is_int($key) && is_string($value)
                    ? [$value => fn () => $this->resource->{$value}]
                    : [$key => $value]
            )
            // Gate the keys *before* resolving their values, so nothing is computed for a field that is about to be dropped anyway.
            ->reject(fn ($value, $key) => ! $this->shouldIncludeResourceKey((string) $key, $fields))
            ->map(fn ($value) => value($value, $request))
            ->all();

        $resolved = (new Collection($this->filter($data)))
            // Also gate the keys *after* resolving their values, because `filter()` merges any `merge()` values into the data.
            ->reject(fn ($value, $key) => ! $this->shouldIncludeResourceKey((string) $key, $fields))
            ->map(fn ($value) => $this->wrapValueWithResource($value));

        $resolved->each(function ($value, $key) use ($fields) {
            if ($value instanceof JunctionResource || $value instanceof AnonymousResourceCollection) {
                $value->pluckedFields($fields->nested($key));
            }
        });

        return $resolved->all();
    }

    /**
     * Wrap a raw value in a junction resource.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function wrapValueWithResource(mixed $value): mixed
    {
        return match (true) {
            $value instanceof EloquentCollection => JunctionResource::collection($value),
            $value instanceof Model => new JunctionResource($value),
            default => $value,
        };
    }

    /**
     * Whether the given key should be part of the resource's output.
     *
     * @param string $key
     * @param PluckedFields $fields
     * @return bool
     */
    protected function shouldIncludeResourceKey(string $key, PluckedFields $fields): bool
    {
        if (! $this->resource instanceof Model) {
            return true;
        }

        if ($key === $this->resource->getKeyName()) {
            return true;
        }

        if ($this->resource->isRelation($key)) {
            return $fields->includesRelation($key);
        }

        return $fields->includesAttribute($key);
    }
}
