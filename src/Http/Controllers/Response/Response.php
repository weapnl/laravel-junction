<?php

namespace Weap\Junction\Http\Controllers\Response;

use Closure;

abstract class Response
{
    /**
     * @param Closure $param
     * @return static
     */
    abstract public function modify(Closure $param): static;
}
