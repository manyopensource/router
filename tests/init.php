<?php

require __DIR__ . '/../vendor/autoload.php';

class State
{
    protected static $vars = [];

    public static function set($name, $value)
    {
        self::$vars[$name] = $value;
    }

    public static function get($name)
    {
        return static::$vars[$name] ?? null;
    }

    public static function push($name, $value)
    {
        static::$vars[$name][] = $value;
    }
}


/**
 * Dummy functions for testing callbacks
 */
function controllerFunc()
{
    return 'func:foo';
}

class Controller
{
    public static function staticIndex()
    {
        return 'static:foo';
    }

    public function index()
    {
        return 'instance:foo';
    }
}
