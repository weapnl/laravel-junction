<?php

namespace Weap\Junction\Http\Controllers\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
                    is_array($this->pluckAttributes[$field] ?? null) ? $this->pluckAttributes[$field] : null,
                    is_array($this->pluckAccessors[$field] ?? null) ? $this->pluckAccessors[$field] : null,
                    is_array($this->pluckRelations[$field] ?? null) ? $this->pluckRelations[$field] : null,
                );

                return $resourceCollection->toArray($request);
            }

            $resource = new $resourceClass($this->resource->$field);

            $resource->pluckFields(
                is_array($this->pluckAttributes[$field] ?? null) ? $this->pluckAttributes[$field] : null,
                is_array($this->pluckAccessors[$field] ?? null) ? $this->pluckAccessors[$field] : null,
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

        // If no pluck attributes or available attributes are given, return all attributes that are not hidden
        if ($pluckAttributes === null && $attributes === null) {
            return $this->resource->only(
                collect(array_keys($this->resource->getAttributes()))->reject(fn ($attribute) => $this->isHidden($attribute))->all(),
            );
        }
        if ($pluckAttributes !== null) {
            // Always add the primary key
            $pluckAttributes[$this->resource->getKeyName()] = null;

            // Only get available attributes (if present). Accessors are already restricted by `allowsAccessor()`; the primary
            // key is not, because it is added above without that check, so it is only returned when it is available.
            $attributes = $attributes !== null
                ? [
                    ...collect($attributes)->filter(fn ($field) => array_key_exists($field, $pluckAttributes))->all(),
                    ...collect(array_keys($pluckAttributes))
                        ->filter(fn ($field) => $field !== $this->resource->getKeyName() && static::isAccessor($this->resource, $field))
                        ->all(),
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
     * The name must be a plain name, an accessor of the model, never a column, and can not be hidden. When
     * `availableAccessors()` returns a list, the name must also be in it.
     *
     * @param string $accessor
     * @return bool
     */
    public function allowsAccessor(string $accessor): bool
    {
        if (! $this->resource instanceof Model || ! static::isPlainName($accessor)) {
            return false;
        }

        if (! static::isAccessor($this->resource, $accessor) || $this->isHidden($accessor)) {
            return false;
        }

        $availableAccessors = $this->availableAccessors();

        return $availableAccessors === null || static::containsField($availableAccessors, $accessor);
    }

    /**
     * Determine whether the given field may be plucked from the model of this resource.
     *
     * The field must be a plain name. The primary key is always allowed, so clients can always request it; the response
     * only returns it when `availableAttributes()` returns `null` or lists it. An accessor must be allowed by
     * `allowsAccessor()`. A relation, or any other method of the model, is not a field. Any other field is a column, which
     * can not be hidden and must be in `availableAttributes()` with the exact same name when that returns a list. Whether
     * a column exists is only known for a loaded record.
     *
     * @param string $field
     * @return bool
     */
    public function allowsPluckField(string $field): bool
    {
        if (! $this->resource instanceof Model || ! static::isPlainName($field)) {
            return false;
        }

        if ($field === $this->resource->getKeyName()) {
            return true;
        }

        if (static::isAccessor($this->resource, $field)) {
            return $this->allowsAccessor($field);
        }

        if ($this->resource->isRelation($field) || $this->isHidden($field)) {
            return false;
        }

        $availableAttributes = $this->availableAttributes();

        // The response looks columns up by their exact name, so a different spelling would be allowed but never returned.
        return $availableAttributes === null || in_array($field, $availableAttributes, true);
    }

    /**
     * Get the resource class for the given relation, or null if the relation is not allowed.
     *
     * A relation that `availableRelations()` maps to a resource class uses that class. A relation it maps to `null`, or
     * every relation when it returns `null`, uses the resource that `Junction::resourceFor()` resolves for the related
     * model.
     *
     * @param string $relation
     * @param Model|null $related The related model, used to resolve its own resource.
     * @return class-string<BaseResource>|null
     */
    public function resourceForRelation(string $relation, ?Model $related = null): ?string
    {
        $availableRelations = $this->availableRelations();

        if ($availableRelations !== null) {
            if (! array_key_exists($relation, $availableRelations)) {
                return null;
            }

            if ($availableRelations[$relation] !== null) {
                return $availableRelations[$relation];
            }
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

        if (! array_key_exists($field, $this->resource->getAttributes()) && ! static::isAccessor($this->resource, $field)) {
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
     * @param string $field
     * @return bool
     */
    protected function isHidden(string $field): bool
    {
        return static::isHiddenOn($this->resource, $field);
    }

    /**
     * Determine whether the given field is hidden on the model: in `$hidden`, or missing from `$visible` when that is set.
     *
     * @param Model $model
     * @param string $field
     * @return bool
     */
    public static function isHiddenOn(Model $model, string $field): bool
    {
        if (static::containsField($model->getHidden(), $field)) {
            return true;
        }

        $visible = $model->getVisible();

        return $visible !== [] && ! static::containsField($visible, $field);
    }

    /**
     * Laravel also treats `-` and spaces as word separators when it looks up the accessor of a name, so a name with any
     * other character than letters, digits and underscores could reach an accessor without matching its `$hidden` entry.
     *
     * @param string $field
     * @return bool
     */
    public static function isPlainName(string $field): bool
    {
        // `\z`, because `$` also matches before a trailing newline.
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*\z/', $field) === 1;
    }

    /**
     * Compare field names the way PHP finds the accessor method of a plain name: ignoring case and underscores. Requests
     * may use camelCase or snake_case, and any other spelling of a plain name reaches the same method.
     *
     * @param array $fields
     * @param string $field
     * @return bool
     */
    protected static function containsField(array $fields, string $field): bool
    {
        $field = static::normalizeField($field);

        foreach ($fields as $candidate) {
            if (is_string($candidate) && static::normalizeField($candidate) === $field) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $field
     * @return string
     */
    protected static function normalizeField(string $field): string
    {
        return strtolower(str_replace('_', '', $field));
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
