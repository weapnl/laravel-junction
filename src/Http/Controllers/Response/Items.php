<?php

namespace Weap\Junction\Http\Controllers\Response;

use Closure;
use Illuminate\Contracts\Pagination\Paginator;
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
     * @var Paginator|null
     */
    protected ?Paginator $paginator = null;

    /**
     * @var bool
     */
    protected bool $simplePagination = false;

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
     * @param bool $simplePagination
     * @return $this
     */
    public function simplePagination(bool $simplePagination): Items
    {
        $this->simplePagination = $simplePagination;

        return $this;
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

            if ($this->simplePagination) {
                $this->paginator = $this->query->simplePaginate($perPage, $columns, 'page', $page);
            } else {
                $this->paginator = $this->query->paginate($perPage, $columns, 'page', $page);
            }

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
     * @return Paginator|null
     */
    public function paginator(): ?Paginator
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

        $keyName = $this->query->getModel()->getKeyName();

        $index = $this->query->toBase()
            ->clone()
            ->select([$this->query->getModel()->getTable() . '.' . $keyName])
            ->cursor()
            ->search(function ($data) use ($idToFind, $keyName) {
                return $data->$keyName == $idToFind;
            });

        if ($index === false) {
            return $page;
        }

        return ceil(($index + 1) / $perPage);
    }
}
