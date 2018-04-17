<?php namespace Maer\Router;

use Maer\Router\Exceptions\MethodNotAllowedException;
use Maer\Router\Exceptions\NotFoundException;

class RouteCollection
{
    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $names = [];


    /**
     * Add a route
     *
     * @param array $route
     */
    public function add(array $route)
    {
        if (!empty($route['name'])) {
            $this->names[$route['name']] = $route['pattern'];
        }

        if (empty($this->routes[$route['host']])) {
            $this->routes[$route['host']] = [];
        }

        foreach ($route['methods'] as $method) {
            $method = strtolower($method);
            if (empty($this->routes[$route['host']][$route['pattern']])) {
                $this->routes[$route['host']][$route['pattern']] = [];
            }

            $this->routes[$route['host']][$route['pattern']][$method] = $route;
        }
    }


    /**
     * Get a route by name
     *
     * @param  string $name
     * @param  array  $params
     *
     * @return string
     */
    public function getRouteByName($name, array $params = [])
    {
        if (empty($this->names[$name])) {
            return null;
        }

        if (!$route) {
            return null;
        }

        return $this->routes[$name];
    }


    /**
     * Get routes
     *
     * @param  string $host
     *
     * @return array
     */
    public function getRoutes($host = null)
    {
        return $this->routes;
    }
}
