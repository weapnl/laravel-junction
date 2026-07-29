<?php

namespace Weap\Junction\Http\Resources\Concerns;

use Illuminate\Http\Request;
use Weap\Junction\Http\Requests\JunctionRequest;

trait ResolvesJunctionRequest
{
    /**
     * Resolve a JunctionRequest instance from the given HTTP request, so the
     * resource can read its plucked fields straight from the request instead
     * of having them pushed in from the outside.
     *
     * @param Request $request
     * @return JunctionRequest
     */
    protected function resolveJunctionRequestFrom(Request $request): JunctionRequest
    {
        return $request instanceof JunctionRequest
            ? $request
            : JunctionRequest::createFrom($request);
    }
}
