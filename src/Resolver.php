<?php namespace Maer\Router;

class Resolver
{
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
}
