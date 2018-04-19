<?php namespace Maer\Router;

use Maer\Router\Exceptions\MethodNotAllowedException;
use Maer\Router\Exceptions\NotFoundException;
use InvalidArgumentException;

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
     * @var Tester
     */
    protected $tester;


    /**
     * @param Tester $tester
     */
    public function __construct(Tester $tester)
    {
        $this->tester = $tester;
    }


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
     * @param  array  $args
     *
     * @return string
     */
    public function getRouteByName($name, array $args = [])
    {
        if (empty($this->names[$name])) {
            return null;
        }

        $pattern = $this->names[$name];

        if (strpos($pattern, '(') === false) {
            // If we don't have any route parameters, just return the pattern
            // straight off. No need for any regex stuff.
            return $pattern;
        }

        // Convert all placeholders to %o = optional and %r = required
        $from    = ['/(\([^\/]+[\)]+[\?])/', '/(\([^\/]+\))/'];
        $to      = ['%o', '%r'];
        $pattern = preg_replace($from, $to, $pattern);

        $frags = explode('/', trim($pattern, '/'));
        $url   = [];

        // Loop thru the pattern fragments and insert the arguments
        foreach ($frags as $frag) {
            if ($frag == '%r') {
                if (!$args) {
                    // A required parameter, but no more arguments.
                    throw new InvalidArgumentException('Missing route parameters');
                }

                $url[] = array_shift($args);
                continue;
            }

            if ($frag == "%o") {
                if (!$args) {
                    // No argument for the optional parameter,
                    // just continue the iteration.
                    continue;
                }

                $url[] = array_shift($args);
                continue;
            }

            $url[] = $frag;
        }

        return '/' . implode('/', $url);
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
        $routes = $this->routes['*'] ?? [];

        if ($host) {
            foreach ($this->routes as $name => $items) {
                if ('*' === $name) {
                    continue;
                }

                if ($this->tester->match($name, $host)) {
                    $routes = array_replace_recursive($routes, $items);
                }
            }
        }

        return $routes;
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
    public function getMatchingRoute($method, $path, $host = null)
    {
        $method   = strtolower($method);
        $response = [
            'status' => Router::OK,
            'match'  => [],
        ];

        $requestArgs = [];
        $notAllowed  = false;

        foreach ($this->getRoutes($host) as $pattern => $route) {
            $ok = true;
            $matches = $this->tester->match($pattern, $path);

            if ($matches) {
                $match = [];
                $args  = $this->getMatchArgs($matches);

                if (!empty($route[$method])) {
                    $match = $route[$method];
                } else if (!empty($route['any'])) {
                    $match = $route['any'];
                } else {
                    $requestArgs = $args;
                    $notAllowed  = true;
                    $ok = false;
                }

                $match['args'] = $args;
                $response['match'] = $match;

                if ($ok) {
                    return $response;
                }

                // We might find a better matching route so let's keep checking.
                // If we don't, we have our NOT ALLOWED info.
            }
        }

        $response['match']['args'] = $requestArgs;
        $response['status'] = $notAllowed
            ? Router::METHOD_NOT_ALLOWED
            : Router::NOT_FOUND;

        return $response;
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
}
