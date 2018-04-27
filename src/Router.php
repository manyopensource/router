<?php namespace Maer\Router;

use Closure;
use Exception;
use InvalidArgumentException;
use Maer\Router\Exceptions\ControllerNotFoundException;
use Maer\Router\Exceptions\MethodNotAllowedException;
use Maer\Router\Exceptions\NotFoundException;

class Router
{
    /**
     * Status codes
     */
    const OK = 200;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var Groups
     */
    protected $groups;

    /**
     * @var Closure|Resolver
     */
    protected $resolver;

    /**
     * @var Tester
     */
    protected $tester;

    /**
     * @var array
     */
    protected $filters   = [];

    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * @var mixed
     */
    protected $notFound;

    /**
     * @var mixed
     */
    protected $methodNotAllowed;

    /**
     * @var array
     */
    protected $errors    = [
        'notFound'         => null,
        'methodNotAllowed' => null,
    ];


    /**
     * @param Resolver|null $resolver
     */
    public function __construct(Resolver $resolver = null)
    {
        if ($resolver) {
            $this->setResolver($resolver);
        }

        // Set the default error handlers
        $this->notFound = function () {
            throw new NotFoundException;
        };

        $this->methodNotAllowed = function () {
            throw new MethodNotAllowedException;
        };
    }


    /**
     * Set the resolver for callbacks of the type: ['Classname', 'method']
     *
     * @param  Resolver $resolver
     *
     * @return $this
     */
    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;

        return $this;
    }


    /**
     * Get the resolver
     *
     * @return Resolver
     */
    public function getResolver()
    {
        return is_null($this->resolver)
            ? $this->resolver = new Resolver
            : $this->resolver;
    }


    /**
     * Set the tester
     *
     * @param  Tester $tester
     *
     * @return $this
     */
    public function setTester(Tester $tester)
    {
        $this->tester = $tester;

        return $this;
    }


    /**
     * Get the tester
     *
     * @return Tester
     */
    public function getTester()
    {
        return is_null($this->tester)
            ? $this->tester = new Tester
            : $this->tester;
    }


    /**
     * Set the route collection
     *
     * @param  RouteCollection $routes
     *
     * @return $this
     */
    public function setRouteCollection(RouteCollection $routes)
    {
        $this->routes = $routes;

        return $this;
    }


    /**
     * Get the route collection handler
     *
     * @return RouteCollection
     */
    public function getRouteCollection()
    {
        return is_null($this->routes)
            ? $this->routes = new RouteCollection($this->getTester())
            : $this->routes;
    }


    /**
     * Set the groups handler
     *
     * @param  Groups $groups
     *
     * @return $this
     */
    public function setGroups(Groups $groups)
    {
        $this->groups = $groups;

        return $this;
    }


    /**
     * Get the groups handler
     *
     * @return Groups
     */
    public function getGroups()
    {
        return is_null($this->groups)
            ? $this->groups = new Groups
            : $this->groups;
    }


    /**
     * Add a route with a specific HTTP verb
     *
     * @param  string $method
     * @param  array  $args
     *
     * @throws Exception If the method isn't one of the registerd HTTP verbs
     *
     * @return $this
     */
    public function __call($method, $args)
    {
        $method = strtoupper($method);
        $verbs  = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS', 'CONNECT', 'TRACE', 'ANY'];

        if (!in_array($method, $verbs)) {
            throw new Exception("Call to undefined method '{$method}'");
        }

        array_unshift($args, $method);

        return call_user_func_array([$this, 'add'], $args);
    }


    /**
     * Add a new route
     *
     * @param string          $method
     * @param string          $pattern
     * @param callable|string $callback
     * @param array           $options
     *
     * @return $this
     */
    public function add($method, $pattern, $callback, array $options = [])
    {
        $route            = $this->getGroups()->appendGroupInfo($pattern, $options);
        $route['methods'] = !is_array($method) ? [$method] : $method;

        // Add the callback to the stack and replace it with the index
        $this->callbacks[] = $callback;
        $route['callback'] = count($this->callbacks) - 1;

        $this->getRouteCollection()->add($route);

        return $this;
    }


    /**
     * Add a route group
     *
     * @param  array   $option
     * @param  Closure $callback
     *
     * @return $this
     */
    public function group(array $options, Closure $callback)
    {
        $this->getGroups()->push($options);
        call_user_func_array($callback, [$this]);
        $this->getGroups()->pop();

        return $this;
    }


    /**
     * Add a new route filter
     *
     * @param  string          $name
     * @param  callable|string $callback
     *
     * @return $this
     */
    public function filter($name, $callback)
    {
        $this->filters[$name] = $callback;

        return $this;
    }


    /**
     * Get a filter
     *
     * @param  string   $name
     *
     * @return mixed
     *
     * @throws Exception If the filter is unknown
     */
    public function getFilter($name)
    {
        if (empty($this->filters[$name])) {
            throw new Exception("Unknown filter '{$name}'");
        }

        return $this->filters[$name];
    }


    /**
     * Get matching route
     *
     * @param  string $method
     * @param  string $path
     *
     * @return object
     *
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     * @throws Exception if an unknown status occurred
     */
    public function getMatch($method = null, $path = null, $host = null)
    {
        list($method, $path, $host) = $this->normalizeRequest($method, $path, $host);

        $response = $this->getRouteCollection()->getMatchingRoute($method, $path, $host);

        switch ($response['status']) {
            case self::METHOD_NOT_ALLOWED:
                $response['match']['callback'] = $this->methodNotAllowed;
                break;
            case self::NOT_FOUND:
                $response['match']['callback'] = $this->notFound;
                break;
            case self::OK:
                if (is_int($response['match']['callback'])) {
                    $response['match']['callback'] = $this->callbacks[$response['match']['callback']];
                }
                break;
            default:
                // It should never come to this point, but just in case.
                throw new Exception('An unknown exception occurred when matching routes');
                break;
        }

        $response['match']['status'] = $response['status'];

        return $response['match'];
    }


    /**
     * Get matching route and dispatch all filters and callbacks
     *
     * @param  string $method
     * @param  string $path
     *
     * @return mixed
     *
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function dispatch($method = null, $path = null, $host = null)
    {
        list($method, $path, $host) = $this->normalizeRequest($method, $path, $host);

        $match  = $this->getMatch($method, $path, $host);

        if (!$match) {
            return;
        }

        // Set the correct status code
        http_response_code($match['status']);

        // Run all before filters
        foreach ($match['before'] ?? [] as $filter) {
            $response = $this->getResolver()->execute($this->getFilter($filter), $match['args']);
            if (!is_null($response)) {
                // This returned something. Stop and return that response.
                return $response;
            }
        }

        // Execute the route callback
        $response = $this->getResolver()->execute($match['callback'], ($match['args'] ?? []));

        // Run all the after filters, add the response (as a reference) as the first param to all filters

        if (!empty($match['after'])) {
            $args = array_merge([&$response], ($match['args'] ?? []));

            foreach ($match['after'] ?? [] as $filter) {
                $after = $this->getResolver()->execute($this->getFilter($filter), $args);

                if ($after) {
                    $response = $after;
                }
            }
        }

        return $response;
    }


    /**
     * Normalize the request params
     *
     * @param  string $method
     * @param  string $path
     * @param  string $host
     *
     * @return array ['method', 'path', 'host']
     */
    protected function normalizeRequest($method = null, $path = null, $host = null)
    {
        return [
            strtolower($method ?? $this->getRequestMethod()), // Method
            '/' . trim($path ?? $this->getRequestPath(), '/'), // Path
            $host ?? $this->getHost(), // Host
        ];
    }


    /**
     * Get registered tokens
     *
     * @return array
     */
    public function getTokens()
    {
        return $this->getTester()->getTokens();
    }


    /**
     * Add regex token
     *
     * @param string $name
     * @param string $pattern
     *
     * @return $this
     */
    public function addToken($name, $pattern)
    {
        $this->getTester()->addToken($name, $pattern);

        return $this;
    }


    /**
     * Get the requested HTTP method
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper($_SERVER['REQUEST_METHOD'])
            : null;
    }


    /**
     * Get the requested URL path
     *
     * @return string
     */
    public function getRequestPath()
    {
        return isset($_SERVER['REQUEST_URI'])
            ? '/' . trim(strtok($_SERVER['REQUEST_URI'], '?'), '/')
            : null;
    }


    /**
     * Get host
     *
     * @return string
     */
    public function getHost()
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            return parse_url($_SERVER['HTTP_HOST'], PHP_URL_PATH);
        }

        if (!empty($_SERVER['SERVER_NAME'])) {
            return parse_url($_SERVER['SERVER_NAME'], PHP_URL_PATH);
        }

        return null;
    }


    /**
     * Add a callback for not found
     *
     * @param  callable $callback
     *
     * @return $this
     */
    public function notFound(callable $callback)
    {
        $this->notFound = $callback;

        return $this;
    }


    /**
     * Add a callback for method not allowed
     *
     * @param  callable $callback
     *
     * @return $this
     */
    public function methodNotAllowed(callable $callback)
    {
        $this->methodNotAllowed = $callback;

        return $this;
    }


    /**
     * Get the URL of a named route
     *
     * @param  string $name
     * @param  array  $args
     *
     * @return string
     *
     * @throws Exception If there aren't enough arguments for all required parameters
     */
    public function getRoute($name, array $args = [])
    {
        return $this->getRouteCollection()->getRouteByName($name, $args);
    }
}
