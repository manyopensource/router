<?php namespace Maer\Router;

use Closure;
use InvalidArgumentException;
use Maer\Router\Exceptions\ControllerNotFoundException;
use Maer\Router\Exceptions\MethodNotAllowedException;
use Maer\Router\Exceptions\NotFoundException;

class Router
{
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
     * @var array
     */
    protected $patterns  = [
        'all'      => '.*',
        'alphanum' => '[a-zA-Z0-9]+',
        'alpha'    => '[a-zA-Z]+',
        'num'      => '[\-]?[\d\,\.]+',
        'any'      => '[^\/]+',
    ];


    /**
     * @param Resolver|null $resolver
     */
    public function __construct(Resolver $resolver = null)
    {
        $this->routes = new RouteCollection;
        $this->groups = new Groups;
        $this->resolver($resolver ?? new Resolver);
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
        $pattern = '/' . trim($pattern, '/');
        $route   = $this->groups->appendGroupInfo($pattern, $options);

        $route['methods']  = !is_array($method) ? [$method] : $method;

        // Add the callback to the stack and get the index
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
        $method = strtolower($method ?? $this->getRequestMethod());
        $path   = '/' . trim($path ?? $this->getRequestPath(), '/');
        $host   = $host ?? $this->getHost();

        $routes = $this->routes->getRoutes($host);

        foreach ($this->getRoutes() as $pattern => $route) {
            preg_match($this->regexifyPattern($pattern), $path, $matches);

            if ($matches) {
                $match = [];
                if (!empty($route[$method])) {
                    $match = $route[$method];
                } else if (!empty($route['any'])) {
                    $match = $route['any'];
                }

                $args = $this->getMatchArgs($matches);

                if (!$match) {
                    if (!$this->methodNotAllowed) {
                        throw new MethodNotAllowedException;
                    }

                    http_response_code(405);
                    $match['callback'] = $this->methodNotAllowed;
                }

                $match['args']     = $args;
                $match['callback'] = is_int($match['callback'])
                    ? $this->callbacks[$match['callback']]
                    : $match['callback'];

                return $match;
            }
        }

        if (!$this->notFound) {
            throw new NotFoundException;
        }

        http_response_code(404);

        $match = [
            'callback' => $this->notFound,
            'args'     => [],
        ];

        return $match;
    }


    protected function getRoutes()
    {

    }


    /**
     * Get and clean route arguments
     *
     * @param  array $match
     *
     * @return array
     */
    protected function getMatchArgs(array $match)
    {
        // Remove the first element, the matching regex
        array_shift($match);

        // Iterate through the arguments and remove any unwanted slashes
        foreach ($match as &$arg) {
            $arg = trim($arg, '/');
        }

        return $match;
    }


    /**
     * Replace placeholders to regular expressions
     *
     * @param  string $pattern
     *
     * @return string
     */
    protected function regexifyPattern($pattern)
    {
        preg_match_all('/(\/?)\(:([^)]*)\)(\??)/', $pattern, $regExPatterns, PREG_SET_ORDER, 0);

        $pattern = preg_quote($pattern, '/');

        foreach ($regExPatterns as $regExPattern) {
            if (!empty($regExPattern[2]) && key_exists($regExPattern[2], $this->patterns)) {
                $replacement = sprintf(
                    '(%s%s)%s',
                    empty($regExPattern[1]) ? '' : '\/',
                    $this->patterns[$regExPattern[2]],
                    $regExPattern[3]
                );

                $pattern = str_replace(preg_quote($regExPattern[0], '/'), $replacement, $pattern);
            }
        }

        return "/^$pattern$/";
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
            $response = $this->executeCallback($match['callback'], $match['args'], true);
            if ($response) {
                // This returned something. Stop and return that response.
                return $response;
            }
        }

        // Execute the route callback
        $response = $this->executeCallback($match['callback'], $match['args']);

        // Run all the after filters, add the response (as a reference) as the first param to all filters
        $args = array_merge([&$response], $match['args']);
        foreach ($match['after'] ?? [] as $filter) {
            $this->executeCallback($match['callback'], $args, true);
        }

        return $response;
    }


    /**
     * Get registered patterns
     *
     * @return array
     */
    public function getPatterns()
    {
        return $this->patterns;
    }


    /**
     * Add regex pattern
     *
     * @param string $name
     * @param string $pattern
     */
    public function addPattern($name, $pattern)
    {
        $this->patterns[":{$name}"] = $pattern;
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
        if ($cb instanceof Closure) {
            return call_user_func_array($cb, $args);
        }

        if (is_string($cb) && strpos($cb, "@") !== false) {
            $cb = explode('@', $cb);
        }

        if (is_array($cb) && count($cb) == 2) {
            if (!is_object($cb[0])) {
                $cb = $this->resolveCallback($cb);
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
        return $this->routes->getRouteByName($name);
    }
}
