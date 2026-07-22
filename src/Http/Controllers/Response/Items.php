<?php

namespace Weap\Junction\Http\Controllers\Response;

use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;

class Items extends Response
{
    /**
     * @var Builder<Model>
     */
    protected Builder $query;

    /**
     * @var Enumerable<int, Model>
     */
    protected Enumerable $models;

    /**
     * @var Paginator<int, Model>|null
     */
    protected ?Paginator $paginator = null;

    /**
     * @var bool
     */
    protected bool $simplePagination = false;

    /**
     * @var bool
     */
    protected bool $enforceOrderByModelKey = false;

    /**
     * @var string|null
     */
    protected ?string $enforceOrderByModelKeyDirection = null;

    /**
     * @param Builder<Model> $query
     * @return self
     */
    public static function query(Builder $query): self
    {
        $items = new self();

        $items->query = $query;

        return $items;
    }

    /**
     * @param bool $simplePagination
     * @return static
     */
    public function simplePagination(bool $simplePagination): static
    {
        $this->simplePagination = $simplePagination;

        return $this;
    }

    /**
     * @param bool $enforceOrderByModelKey
     * @param string|null $direction
     * @return static
     */
    public function enforceOrderByModelKey(bool $enforceOrderByModelKey, ?string $direction = 'asc'): static
    {
        $this->enforceOrderByModelKey = $enforceOrderByModelKey;
        $this->enforceOrderByModelKeyDirection = $direction;

        return $this;
    }

    /**
     * @return static
     */
    public function get(): static
    {
        $columns = [$this->query->getModel()->getTable() . '.*'];
        $perPage = request()?->input('paginate');

        $this->handleEnforceOrderByModelKey();

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
     * @return static
     */
    public function modify(Closure $param): static
    {
        $this->models->each($param);

        return $this;
    }

    /**
     * @return Enumerable<int, Model>
     */
    public function models(): Enumerable
    {
        return $this->models;
    }

    /**
     * @return Paginator<int, Model>|null
     */
    public function paginator(): ?Paginator
    {
        return $this->paginator;
    }

    /**
     * @param int $perPage
     * @return int|null
     */
    protected function page(int $perPage): ?int
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

        return (int) ceil(($index + 1) / $perPage);
    }

    /**
     * @return void
     */
    protected function handleEnforceOrderByModelKey(): void
    {
        if (! $this->enforceOrderByModelKey) {
            return;
        }

        $baseQuery = $this->query->getQuery();
        $combinedQueryOrders = [
            ...($baseQuery->orders ?? []),
            ...($baseQuery->unionOrders ?? []),
        ];

        $model = $this->query->getModel();
        $modelKeyName = $model->getKeyName();
        $modelQualifiedKeyName = $model->getQualifiedKeyName();

        $hasOrderByModelKey = collect($combinedQueryOrders)->whereIn('column', [$modelKeyName, $modelQualifiedKeyName])->isNotEmpty();

        if (! $hasOrderByModelKey) {
            $this->query->orderBy($modelQualifiedKeyName, $this->enforceOrderByModelKeyDirection ?? 'asc');
        }
    }
}
