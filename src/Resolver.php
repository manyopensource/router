<?php namespace Maer\Router;

use Maer\Router\Exceptions\ControllerNotFoundException;
use Exception;

class Resolver
{
    /**
     * Resolve a callback
     *
     * @param array $callback
     *
     * @return array
     *
     * @throws ControllerNotFoundException
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
     * @param  mixed   $cb
     * @param  array   $args
     *
     * @return mixed
     *
     * @throws ControllerNotFoundException If the callback isn't in one of the accepted formats
     */
    public function execute($cb, array $args = [])
    {
        $valid = true;

        if (is_string($cb) && strpos($cb, "@") !== false) {
            $cb = explode('@', $cb);
        }

        if (is_array($cb) && count($cb) == 2) {
            if (!is_object($cb[0])) {
                $cb = $this->resolve($cb);
            }

            if (isset($cb[0], $cb[1]) && is_object($cb[0]) && !method_exists($cb[0], $cb[1])) {
                $name = get_class($cb[0]);
                throw new ControllerNotFoundException("Controller '{$name}->{$cb[1]}' not found");
            }
        }

        if (is_string($cb)) {
            if (strpos($cb, "::") !== false) {
                $parts = explode('::', $cb);
                $valid = $parts[0] && $parts[1] && class_exists($parts[0]) && method_exists($parts[0], $parts[1]);
            } else {
                $valid = function_exists($cb);
            }
        }

        if (!$valid) {
            throw new ControllerNotFoundException('Invalid controller');
        }

        return call_user_func_array($cb, $args);
    }
}
