<?php

namespace Weap\Junction\Http\Controllers\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Weap\Junction\Http\Controllers\Response\Items;
use Weap\Junction\Junction;

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
        if (is_null($this->resource)) {
            return [];
        }

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
        // If no pluck relations are given, return nothing
        if ($this->pluckRelations === null) {
            return [];
        }

        $relations = collect($this->pluckRelations)
            ->filter(fn ($_, $field) => $this->resource->relationLoaded($field) || $this->resource->isRelation($field))
            ->mapWithKeys(function ($_, $field) {
                $value = $this->resource->$field;
                $related = $value instanceof Collection ? $value->first() : $value;

                return [$field => $this->resourceForRelation($field, $related instanceof Model ? $related : null)];
            })
            ->filter();

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
            ? collect($this->pluckAttributes)
                ->filter(fn ($_, $attribute) => ! $this->resource->isRelation($attribute))
                ->filter(fn ($_, $attribute) => $this->isPluckableField($attribute))
                ->toArray()
            : null;

        // If no pluck attributes or available attributes are given, return all attributes
        if ($pluckAttributes === null && $attributes === null) {
            return $this->resource->only(array_keys(Arr::except($this->resource->getAttributes(), $this->resource->getHidden())));
        }
        if ($pluckAttributes !== null) {
            // Always add the primary key
            $pluckAttributes[$this->resource->getKeyName()] = null;

            // Only get available attributes (if present). Accessors are already restricted by `allowsAccessor()`.
            $attributes = $attributes !== null
                ? [
                    ...collect($attributes)->filter(fn ($field) => array_key_exists($field, $pluckAttributes))->all(),
                    ...collect(array_keys($pluckAttributes))->filter(fn ($field) => static::isAccessor($this->resource, $field))->all(),
                ]
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

        // If no pluck accessors are given, return nothing
        if ($this->pluckAccessors === null) {
            return [];
        }

        // A nested array means the requested path continues past the accessor, which appends do not allow.
        $accessors = collect($this->pluckAccessors)
            ->filter(fn ($nested, $accessor) => ! is_array($nested) && ! $this->resource->isRelation($accessor))
            ->filter(fn ($_, $accessor) => $this->allowsAccessor($accessor))
            ->keys();

        return $this->resource->only($accessors->toArray());
    }

    /**
     * Determine whether the given accessor may be appended to the model of this resource.
     *
     * The name must be an accessor of the model, never a column. When `availableAccessors()` returns a list, the name must
     * also be in it.
     *
     * @param string $accessor
     * @return bool
     */
    public function allowsAccessor(string $accessor): bool
    {
        if (! $this->resource instanceof Model || ! static::isAccessor($this->resource, $accessor)) {
            return false;
        }

        $availableAccessors = $this->availableAccessors();

        return $availableAccessors === null || static::containsField($availableAccessors, $accessor);
    }

    /**
     * Determine whether the given field may be plucked from the model of this resource.
     *
     * Hidden fields are never allowed. An accessor must be allowed by `allowsAccessor()`. Any other field is a column, which
     * must be in `availableAttributes()` when that returns a list. Whether a column exists is only known for a loaded record.
     *
     * @param string $field
     * @return bool
     */
    public function allowsPluckField(string $field): bool
    {
        if (! $this->resource instanceof Model) {
            return false;
        }

        if (static::containsField($this->resource->getHidden(), $field)) {
            return false;
        }

        $visible = $this->resource->getVisible();

        if ($visible !== [] && ! static::containsField($visible, $field)) {
            return false;
        }

        if (static::isAccessor($this->resource, $field)) {
            return $this->allowsAccessor($field);
        }

        $availableAttributes = $this->availableAttributes();

        return $availableAttributes === null || static::containsField($availableAttributes, $field);
    }

    /**
     * Get the resource class for the given relation, or null if the relation is not allowed.
     *
     * @param string $relation
     * @param Model|null $related The related model, used to resolve its own resource.
     * @return class-string<BaseResource>|null
     */
    public function resourceForRelation(string $relation, ?Model $related = null): ?string
    {
        $availableRelations = $this->availableRelations();

        if ($availableRelations !== null) {
            return $availableRelations[$relation] ?? null;
        }

        return $related ? Junction::resourceFor($related) : self::class;
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function isPluckableField(string $field): bool
    {
        if ($field === $this->resource->getKeyName()) {
            return true;
        }

        if (! static::isAccessor($this->resource, $field) && ! array_key_exists($field, $this->resource->getAttributes())) {
            return false;
        }

        return $this->allowsPluckField($field);
    }

    /**
     * @param Model $model
     * @param string $field
     * @return bool
     */
    protected static function isAccessor(Model $model, string $field): bool
    {
        return $model->hasGetMutator($field) || $model->hasAttributeGetMutator($field);
    }

    /**
     * Compare field names regardless of casing, because requests may use camelCase or snake_case.
     *
     * @param array $fields
     * @param string $field
     * @return bool
     */
    protected static function containsField(array $fields, string $field): bool
    {
        $field = Str::snake($field);

        return collect($fields)->contains(fn ($candidate) => is_string($candidate) && Str::snake($candidate) === $field);
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
