<?php

namespace Weap\Junction\Http\Controllers\Validators;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Helpers\RelationFinder;
use Weap\Junction\Http\Controllers\Resources\BaseResource;

class FieldPath
{
    /**
     * Walk the given relations from the controller's model, and return the resource of the last related model.
     *
     * The resources wrap an empty model, so no record is loaded. Returns null when a relation does not exist, is not
     * allowed by the resource of its parent, or is a `morphTo` (its related model is only known per record).
     *
     * @param Controller $controller
     * @param array<int, string> $relations
     * @return BaseResource|null
     */
    public static function resource(Controller $controller, array $relations): ?BaseResource
    {
        $resourceClass = $controller->getResourceClass();
        $resource = new $resourceClass(new $controller->model());

        foreach ($relations as $name) {
            $resource = static::relatedResource($resource, $name);

            if (! $resource) {
                return null;
            }
        }

        return $resource;
    }

    /**
     * @param BaseResource $resource
     * @param string $name
     * @return BaseResource|null
     */
    public static function relatedResource(BaseResource $resource, string $name): ?BaseResource
    {
        $relation = RelationFinder::find($resource->resource, $name);

        if (! $relation || $relation instanceof MorphTo) {
            return null;
        }

        $related = $relation->getRelated()->newInstance();
        $resourceClass = $resource->resourceForRelation($name, $related);

        return $resourceClass ? new $resourceClass($related) : null;
    }
}
