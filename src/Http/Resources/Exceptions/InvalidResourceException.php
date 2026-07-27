<?php

namespace Weap\Junction\Http\Resources\Exceptions;

use RuntimeException;

class InvalidResourceException extends RuntimeException
{
    /**
     * Create an exception indicating the given resource is not an Eloquent model.
     *
     * @param mixed $resource
     * @return self
     */
    public static function attemptingToRenderNonModel(mixed $resource): self
    {
        $resourceType = is_object($resource) ? $resource::class : gettype($resource);

        return new self(sprintf(
            'Unable to render resource object for [%s], a JunctionResource renders an Eloquent model.',
            $resourceType
        ));
    }
}
