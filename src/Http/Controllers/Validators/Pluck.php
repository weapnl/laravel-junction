<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\RelationFinder;

class Pluck
{
    /**
     * Every leading segment that is a relation must be allowed by the resource of its parent. The first segment that is
     * not a relation is the field, which the resource of its model must allow; a relation without a declared return type is
     * not a field, so a path through one is refused. Segments after the field are ignored by the response, which returns
     * the whole value of the field.
     *
     * @param Controller $controller
     * @param array $fields
     * @return array
     *
     * @throws ValidationException
     */
    public static function validate(Controller $controller, array $fields): array
    {
        $fields = collect($fields);

        if ($fields->isEmpty()) {
            return [];
        }

        $invalidFields = $fields->reject(fn ($field) => is_string($field) && static::isAllowed($controller, $field));

        if ($invalidFields->isNotEmpty()) {
            throw ValidationException::withMessages([
                'pluck' => 'Invalid pluck fields: ' . $invalidFields->filter(fn ($field) => is_string($field))->join(', '),
            ]);
        }

        return $fields->toArray();
    }

    /**
     * @param Controller $controller
     * @param string $field
     * @return bool
     */
    protected static function isAllowed(Controller $controller, string $field): bool
    {
        $resource = FieldPath::resource($controller, []);

        foreach (explode('.', $field) as $segment) {
            if (! RelationFinder::isRelation($resource->resource, $segment)) {
                return $resource->allowsPluckField($segment);
            }

            // The model behind a morphTo is only known per record, so the response checks the remaining segments.
            if (RelationFinder::find($resource->resource, $segment) instanceof MorphTo) {
                return true;
            }

            $resource = FieldPath::relatedResource($resource, $segment);

            if (! $resource) {
                return false;
            }
        }

        return true;
    }
}
