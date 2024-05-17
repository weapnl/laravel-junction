<?php

namespace Weap\Junction\Http\Controllers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Weap\Junction\Http\Controllers\Response\Items;

class BaseResource extends JsonResource
{
    /**
     * @var array|null
     */
    protected ?array $pluckAttributes = null;

    /**
     * @var array|null
     */
    protected ?array $pluckAccessors = null;

    /**
     * @var array|null
     */
    protected ?array $pluckRelations = null;

    /**
     * @param Items $items
     * @param array|null $pluckAttributes
     * @param array|null $pluckAccessors
     * @param array|null $pluckRelations
     * @return AnonymousResourceCollection
     */
    public static function items(Items $items, ?array $pluckAttributes = null, ?array $pluckAccessors = null, ?array $pluckRelations = null): AnonymousResourceCollection
    {
        static::wrap('items');

        $resourceCollection = static::collection($items->models());

        $resourceCollection->resource->each->pluckFields($pluckAttributes, $pluckAccessors, $pluckRelations);

        if ($paginator = $items->paginator()) {
            $resourceCollection->additional([
                'total' => $paginator instanceof LengthAwarePaginator ? $paginator->total() : null,
                'page' => $paginator?->currentPage(),
                'has_next_page' => $paginator?->hasMorePages(),
            ]);
        }

        return $resourceCollection;
    }

    /**
     * @param array|null $pluckAttributes
     * @param array|null $pluckAccessors
     * @param array|null $pluckRelations
     * @return $this
     */
    public function pluckFields(?array $pluckAttributes = null, ?array $pluckAccessors = null, ?array $pluckRelations = null): static
    {
        $this->pluckAttributes = $pluckAttributes;
        $this->pluckAccessors = $pluckAccessors;
        $this->pluckRelations = $pluckRelations;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge(
            $this->relationsToArray($request),
            $this->attributesToArray($request),
            $this->accessorsToArray($request),
        );
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function relationsToArray(Request $request): array
    {
        $relations = $this->availableRelations();

        // If no pluck relations are given, return nothing
        if ($this->pluckRelations === null) {
            return [];
        }

        // Only get available relations (if present)
        $relations = $relations !== null
            ? collect($relations)->filter(fn ($resource, $field) => array_key_exists($field, $this->pluckRelations))
            : collect($this->pluckRelations)->mapWithKeys(fn ($_, $relation) => [$relation => self::class]);

        return $relations->map(function ($resourceClass, $field) use ($request) {
            if ($this->resource->$field === null) {
                return null;
            }

            if ($this->resource->$field instanceof Collection) {
                $resourceCollection = $resourceClass::collection($this->resource->$field);

                // Run pluck for each resource in the collection
                $resourceCollection->resource->each->pluckFields(
                    $this->pluckAttributes[$field] ?? null,
                    $this->pluckAccessors[$field] ?? null,
                    is_array($this->pluckRelations[$field] ?? null) ? $this->pluckRelations[$field] : null,
                );

                return $resourceCollection->toArray($request);
            }

            $resource = new $resourceClass($this->resource->$field);

            $resource->pluckFields(
                $this->pluckAttributes[$field] ?? null,
                $this->pluckAccessors[$field] ?? null,
                is_array($this->pluckRelations[$field] ?? null) ? $this->pluckRelations[$field] : null,
            );

            return $resource->toArray($request);
        })->toArray();
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function attributesToArray(Request $request): array
    {
        $attributes = $this->availableAttributes();

        $pluckAttributes = $this->pluckAttributes !== null
            ? collect($this->pluckAttributes)->filter(fn ($_, $attribute) => ! $this->resource->isRelation($attribute))->toArray()
            : null;

        // If no pluck attributes or available attributes are given, return all attributes
        if ($pluckAttributes === null && $attributes === null) {
            return $this->resource->only(array_keys(Arr::except($this->resource->getAttributes(), $this->resource->getHidden())));
        }
        if ($pluckAttributes !== null) {
            // Always add the primary key
            $pluckAttributes[$this->resource->getKeyName()] = null;

            // Only get available attributes (if present)
            $attributes = $attributes !== null
                ? collect($attributes)->filter(fn ($field) => array_key_exists($field, $pluckAttributes))->toArray()
                : array_keys($pluckAttributes);
        }

        return $this->resource->only($attributes);
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function accessorsToArray(Request $request): array
    {
        if (method_exists($this->resource, 'defaultAppends')) {
            collect($this->resource::defaultAppends())
                ->each(function ($accessor) {
                    if (isset($this->pluckAccessors[$accessor])) {
                        return;
                    }

                    $this->pluckAccessors[$accessor] = null;
                });
        }

        $accessors = $this->availableAccessors();

        $pluckAccessors = $this->pluckAccessors !== null
            ? collect($this->pluckAccessors)->filter(fn ($_, $accessor) => ! $this->resource->isRelation($accessor))->toArray()
            : null;

        // If no pluck accessors are given, return nothing
        if ($pluckAccessors === null) {
            return [];
        }

        // Only get available accessors (if present)
        $accessors = $accessors !== null
            ? collect($accessors)->filter(fn ($field) => array_key_exists($field, $pluckAccessors))
            : collect(array_keys($pluckAccessors));

        return $this->resource->only($accessors->toArray());
    }

    /**
     * @return array|null
     */
    protected function availableAttributes(): ?array
    {
        return null;
    }

    /**
     * @return array|null
     */
    protected function availableAccessors(): ?array
    {
        return null;
    }

    /**
     * @return array|null
     */
    protected function availableRelations(): ?array
    {
        return null;
    }
}
