<?php

namespace Weap\Junction\Http\Controllers\Modifiers;

use Weap\Junction\Http\Controllers\Controller;
use Weap\Junction\Http\Controllers\Response\Response;

abstract class Modifier
{
    /**
     * @param Controller $controller
     * @param Response $response
     */
    abstract public static function apply(Controller $controller, Response $response): void;
}
