<?php

namespace Weap\Junction\Http\Controllers\Response;

use Closure;

abstract class Response
{
    /**
     * @param Closure $param
     * @return $this
     */
    abstract public function modify(Closure $param): self;
}
