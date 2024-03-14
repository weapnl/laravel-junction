<?php

namespace Weap\Junction;

use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;
use Illuminate\Routing\Route;

class ResourceRegistrar extends BaseResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var string[]
     */
    protected $resourceDefaults = [
        'index', 'indexPost', 'store', 'show', 'showPost', 'update', 'destroy', 'action',
    ];

    /**
     * Add the index method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceIndexPost(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getResourceUri($name) . '/index';

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'index', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the show method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return Route
     */
    protected function addResourceShowPost(string $name, string $base, string $controller, array $options): Route
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name) . '/{' . $base . '}/show';

        $action = $this->getResourceAction($name, $controller, 'show', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the action method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    public function addResourceAction(string $name, string $base, string $controller, array $options): Route
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name);

        $action = $this->getResourceAction($name, $controller, 'action', $options);

        return $this->router->put($uri, $action);
    }
}
