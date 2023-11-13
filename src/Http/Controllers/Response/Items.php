<?php

namespace Weap\Junction\Http\Controllers\Response;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Enumerable;

class Items extends Response
{
    /**
     * @var Builder
     */
    protected Builder $query;

    /**
     * @var Enumerable
     */
    protected Enumerable $models;

    /**
     * @var LengthAwarePaginator
     */
    protected $paginator;

    /**
     * @param Builder $query
     * @return Items
     */
    public static function query(Builder $query): Items
    {
        $items = new self();

        $items->query = $query;

        return $items;
    }

    /**
     * @return $this
     */
    public function get(): self
    {
        $columns = [$this->query->getModel()->getTable() . '.*'];
        $perPage = request()?->input('paginate');

        if ($perPage) {
            $page = $this->page($perPage);

            $this->paginator = $this->query->paginate($perPage, $columns, 'page', $page);

            $this->models = collect($this->paginator->items());

            return $this;
        }

        $this->models = $this->query->get($columns);

        return $this;
    }

    /**
     * @param Closure $param
     * @return $this
     */
    public function modify(Closure $param): self
    {
        $this->models->each($param);

        return $this;
    }

    /**
     * @return Enumerable
     */
    public function models(): Enumerable
    {
        return $this->models;
    }

    /**
     * @return LengthAwarePaginator|null
     */
    public function paginator(): ?LengthAwarePaginator
    {
        return $this->paginator;
    }

    /**
     * @param $perPage
     * @return int|null
     */
    protected function page($perPage): ?int
    {
        $page = request()?->input('page') ?: 1;

        $idToFind = request()?->input('page_for_id');

        if (! $idToFind) {
            return $page;
        }

        if ($this->query->getModel()->getKeyType() === 'int') {
            $idToFind = (int) $idToFind;
        }

        $keyname = $this->query->getModel()->getKeyName();

        $index = $this->query->toBase()
            ->clone()
            ->select([$this->query->getModel()->getTable() . '.' . $keyname])
            ->cursor()
            ->search(function ($data) use ($idToFind, $keyname) {
                return $data->$keyname == $idToFind;
            });

        if ($index === false) {
            return $page;
        }

        return ceil(($index + 1) / $perPage);
    }
}
