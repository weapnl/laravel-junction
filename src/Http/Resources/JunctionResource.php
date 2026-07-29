<?php

namespace Weap\Junction\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Weap\Junction\Http\Resources\Concerns\ResolvesJunctionElements;
use Weap\Junction\Http\Resources\Concerns\ResolvesJunctionRequest;

class JunctionResource extends JsonResource
{
    use ResolvesJunctionElements,
        ResolvesJunctionRequest;

    /**
     * The "data" wrapper that should be applied.
     *
     * Junction resources are returned unwrapped.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * Returns model's attributes/accessors and requested relations when not overridden.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        if (! $this->resource instanceof Model) {
            return (array) parent::toArray($request);
        }

        return [
            ...$this->resource->attributesToArray(), // Attributes and accessors
            ...$this->resolveModelRelations($request), // Relations
        ];
    }

    /**
     * The loaded relations the client requested.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function resolveModelRelations(Request $request): array
    {
        if (! $this->resource instanceof Model) {
            return [];
        }

        $fields = $this->resolvePluckedFields($request);

        return (new Collection($this->resource->getRelations()))
            ->filter(fn ($value, $name) => $fields->includesRelation($name))
            ->all();
    }

    /**
     * Resolve the resource data to an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function resolveResourceData(Request $request)
    {
        return $this->resolveResourceObject($request);
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * Laravel answers a recently created model with a 201;
     * Junction expects all routes to answer with a 200.
     *
     * @param Request $request
     * @param JsonResponse $response
     */
    public function withResponse(Request $request, JsonResponse $response)
    {
        $response->setStatusCode(200);
    }

    /**
     * Create a new resource collection instance.
     *
     * @param mixed $resource
     * @return AnonymousResourceCollection
     */
    protected static function newCollection($resource)
    {
        return new AnonymousResourceCollection($resource, static::class);
    }
}
