<?php

namespace Weap\Junction\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Weap\Junction\Http\Resources\Concerns\ResolvesJunctionRequest;
use Weap\Junction\Support\PluckedFields;

class AnonymousResourceCollection extends \Illuminate\Http\Resources\Json\AnonymousResourceCollection
{
    use ResolvesJunctionRequest;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = 'items';

    /**
     * The fields plucked for the resources in this collection.
     */
    protected ?PluckedFields $pluckedFields = null;

    /**
     * Set the fields plucked for the resources in this collection.
     *
     * @param PluckedFields $fields
     */
    public function pluckedFields(PluckedFields $fields): static
    {
        $this->pluckedFields = $fields;

        return $this;
    }

    /**
     * Transform the resource collection into an array.
     *
     * The field selection is resolved once here and handed to every resource in
     * the collection, so an index response does not rebuild it per record.
     *
     * @param Request $request
     * @return array<int, mixed>
     */
    public function toArray(Request $request)
    {
        $fields = $this->pluckedFields ??= $this->resolveJunctionRequestFrom($request)->pluckedFields();

        $this->collection->each(function ($resource) use ($fields) {
            if ($resource instanceof JunctionResource || $resource instanceof self) {
                $resource->pluckedFields($fields);
            }
        });

        return parent::toArray($request);
    }

    /**
     * Customize the pagination information for the resource. Replaces Laravel's
     * default `links`/`meta` structure and is only called for paginated responses.
     *
     * @param Request $request
     * @param array<string, mixed> $paginated
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    public function paginationInformation($request, $paginated, $default): array
    {
        return [
            'total' => $this->resource instanceof LengthAwarePaginator ? $this->resource->total() : null,
            'page' => $this->resource->currentPage(),
            'has_next_page' => $this->resource->hasMorePages(),
        ];
    }
}
