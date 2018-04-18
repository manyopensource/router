<?php namespace Maer\Router;

use Closure;
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
        $this->resolver($resolver ?? new Resolver);
        $this->tester = new Tester;
        $this->routes = new RouteCollection($this->tester);
        $this->groups = new Groups;

        // Set the default error handlers
        $this->notFound = function () {
            throw new NotFoundException;
        };

        $this->methodNotAllowed = function () {
            throw new MethodNotAllowedException;
        };
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

        if (isset($args[0]) && is_array($args[0])) {
            if (count($args[0]) > 1) {
                if (!isset($args[2]) || !is_array($args[2])) {
                    $args[2] = [];
                }
                $args[2]['name'] = $args[0][1];
            }

            $args[0] = $args[0][0];
        }


        array_unshift($args, $method);
        return call_user_func_array([$this, 'add'], $args);
    }


    /**
     * Add a route group
     *
     * @param  array   $option
     * @param  Closure $callback
     */
    public function group(array $options, Closure $callback)
    {
        $this->groups->push($options);
        call_user_func_array($callback, [$this]);
        $this->groups->pop();
    }


    /**
     * Add a new route
     *
     * @param string $method
     * @param string $pattern
     * @param mixed  $callback
     * @param array  $options
     */
    public function add($method, $pattern, $callback, array $options = [])
    {
        $route            = $this->groups->appendGroupInfo($pattern, $options);
        $route['methods'] = !is_array($method) ? [$method] : $method;

        // Add the callback to the stack and replace it with the index
        $this->callbacks[] = $callback;
        $route['callback'] = count($this->callbacks) - 1;

        $this->routes->add($route);
    }


    /**
     * Add a new route filter
     *
     * @param  string $name
     * @param  mixed  $callback
     *
     * @return $this
     */
    public function filter($name, $callback)
    {
        $this->filters[$name] = $callback;
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
     */
    public function getMatch($method = null, $path = null, $host = null)
    {
        $method   = strtolower($method ?? $this->getRequestMethod());
        $path     = '/' . trim($path ?? $this->getRequestPath(), '/');
        $host     = $host ?? $this->getHost();
        $response = $this->routes->getMatchingRoute($method, $path, $host);

        switch ($response['status']) {
            case self::METHOD_NOT_ALLOWED:
                http_response_code(self::METHOD_NOT_ALLOWED);
                $response['match']['callback'] = $this->methodNotAllowed;
                break;
            case self::NOT_FOUND:
                http_response_code(self::NOT_FOUND);
                $response['match']['callback'] = $this->notFound;
                break;
            case self::OK:
                if (is_int($response['match']['callback'])) {
                    $response['match']['callback'] = $this->callbacks[$response['match']['callback']];
                }
                break;
            default:
                // It should never come to this point, but just in case.
                throw new \Exception('An unknown exception occurred when matching routes');
                break;
        }

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
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    public function dispatch($method = null, $path = null, $host = null)
    {
        $method = strtolower($method ?? $this->getRequestMethod());
        $path   = '/' . trim($path ?? $this->getRequestPath(), '/');
        $host   = $host ?? $this->getHost();

        $match  = $this->getMatch($method, $path, $host);

        if (!$match) {
            return;
        }

        // Run all before filters
        foreach ($match['before'] ?? [] as $filter) {
            $response = $this->executeCallback($filter, $match['args'], true);
            if (!is_null($response)) {
                // This returned something. Stop and return that response.
                return $response;
            }
        }

        // Execute the route callback
        $response = $this->executeCallback($match['callback'], ($match['args'] ?? []));

        // Run all the after filters, add the response (as a reference) as the first param to all filters
        $args = array_merge([&$response], ($match['args'] ?? []));
        foreach ($match['after'] ?? [] as $filter) {
            $this->executeCallback($filter, $args, true);
        }

        return $response;
    }


    /**
     * Get registered tokens
     *
     * @return array
     */
    public function getTokens()
    {
        return $this->tester->getTokens();
    }


    /**
     * Add regex token
     *
     * @param string $name
     * @param string $pattern
     */
    public function addToken($name, $pattern)
    {
        $this->tester->addToken($name, $pattern);
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
     * @param  string|Closure|array $callback
     */
    public function notFound($callback)
    {
        $this->notFound = $callback;
    }


    /**
     * Add a callback for method not allowed
     *
     * @param  string|Closure|array $callback
     */
    public function methodNotAllowed($callback)
    {
        $this->methodNotAllowed = $callback;
    }


    /**
     * Execute a callback
     *
     * @param  mixed   $cb
     * @param  array   $args
     * @param  boolean $filter Set if the callback is a filter or not
     *
     * @return mixed
     *
     * @throws Exception If the filter is unknown
     * @throws Exception If the callback isn't in one of the accepted formats
     */
    public function executeCallback($cb, array $args = [], $filter = false)
    {
        if ($filter && is_string($cb)) {
            $cb = $this->filters[$cb] ?? null;
        }

        if ($cb instanceof Closure) {
            return call_user_func_array($cb, $args);
        }

        if (is_string($cb) && strpos($cb, "@") !== false) {
            $cb = explode('@', $cb);
        }

        if (is_array($cb) && count($cb) == 2) {
            if (!is_object($cb[0])) {
                $cb = $this->resolver->resolve($cb);
            }

            if (isset($cb[0], $cb[1]) && is_object($cb[0]) && !method_exists($cb[0], $cb[1])) {
                $name = get_class($cb[0]);
                throw new ControllerNotFoundException("Controller '{$name}->{$cb[1]}' not found");
            }

            return call_user_func_array($cb, $args);
        }

        if (is_string($cb) && strpos($cb, "::") !== false) {
            return call_user_func_array($cb, $args);
        }

        throw new InvalidArgumentException('Invalid callback');
    }


    /**
     * Add a resolver for callbacks of the type: ['Classname', 'method']
     *
     * @param  callable $resolver
     *
     * @return $this
     */
    public function resolver($resolver)
    {
        if (!$resolver instanceof Closure && !$resolver instanceof Resolver) {
            throw new InvalidArgumentException('A resolver must either be a closure or a class extending Maer\Router\Resolver');
        }

        $this->resolver = $resolver;
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
        return $this->routes->getRouteByName($name, $args);
    }
}
