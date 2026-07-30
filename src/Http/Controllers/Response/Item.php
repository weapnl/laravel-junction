<?php

namespace Weap\Junction\Http\Controllers\Response;

use Closure;
use Illuminate\Database\Eloquent\Model;

class Item extends Response
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @param Model $model
     * @return static
     */
    public static function model(Model $model): static
    {
        $item = new static();

        $item->model = $model;

        return $item;
    }

    /**
     * @param Closure $param
     * @return static
     */
    public function modify(Closure $param): static
    {
        $param($this->model);

        return $this;
    }

    /**
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
