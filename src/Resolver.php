<?php namespace Maer\Router;

use Maer\Router\Exceptions\ControllerNotFoundException;
use Exception;

class Resolver
{
    /**
     * Resolve and instantiate an array callback
     *
     * @param  array  $callback
     *
     * @return array
     */
    public function resolve(array $callback)
    {
        if (!class_exists($callback[0])) {
            throw new ControllerNotFoundException("Controller '{$callback[0]}' not found");
        }

        return [
            new $callback[0],
            $callback[1]
        ];
    }


    /**
     * Execute a callback
     *
     * @param  callable|string $cb
     * @param  array           $args
     *
     * @return mixed
     */
    public function execute($cb, array $args = [])
    {
        $check = true;

        if (is_string($cb) && strpos($cb, '@')) {
            $cb = explode('@', $cb);
        }

        if (is_array($cb) && count($cb) == 2) {
            if (!is_object($cb[0])) {
                $cb = $this->resolve($cb);
            }

            if (isset($cb[0], $cb[1]) && is_object($cb[0]) && !method_exists($cb[0], $cb[1])) {
                $name = get_class($cb[0]);
                throw new ControllerNotFoundException("Callback '{$name}->{$cb[1]}' not found");
            }
        }

        if (is_string($cb)) {
            $check = false;

            if (strpos($cb, "::") !== false) {
                $parts = explode('::', $cb);
                $check = $parts[0] && $parts[1] && class_exists($parts[0]) && method_exists($parts[0], $parts[1]);
            } else {
                $check = function_exists($cb);
            }
        }

        if (!$check) {
            throw new ControllerNotFoundException('Invalid callback');
        }

        return call_user_func_array($cb, $args);
    }
}
