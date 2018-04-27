<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class NamedRouteTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test named routes without parameters
     */
    public function testGetNamedRoutes()
    {
        $r = new Router;

        $r->get('/', function () {}, [
            'name' => 'home'
        ]);

        $r->get('/test', function () {}, [
            'name' => 'test'
        ]);

        $this->assertEquals('/', $r->getRoute('home'), 'named:home');
        $this->assertEquals('/test', $r->getRoute('test'), 'named:test');
    }

    /**
     * Test named routes with parameters
     */
    public function testGetNamedRoutesParams()
    {
        $r = new Router;

        $r->get('/test/(:any)', function () {}, [
            'name' => 'test.param'
        ]);

        $r->get('/test/(:any)/(:any)?', function () {}, [
            'name' => 'test.param.optional'
        ]);

        $param    = $r->getRoute('test.param', ['foo']);
        $optional = $r->getRoute('test.param.optional', ['foo', 'bar']);

        $this->assertEquals('/test/foo', $param, 'named:test.param');
        $this->assertEquals('/test/foo/bar', $optional ,'named:test.param.optional');
    }

    /**
     * Test named routes with optional parameters
     */
    public function testGetNamedRoutesOptional()
    {
        $r = new Router;

        $r->get('/test/(:any)/(:any)?', function () {}, [
            'name' => 'test.param.optional'
        ]);

        $optional = $r->getRoute('test.param.optional', ['foo']);

        $this->assertEquals('/test/foo', $optional, 'named:test.optional');
    }

    /**
     * Test exception when missing required parameters
     *
     * @expectedException InvalidArgumentException
     */
    public function testGetNamedRoutesMissingRequrired()
    {
        $r = new Router;

        $r->get('/test/(:any)', function () {}, [
            'name' => 'test.param'
        ]);

        $r->getRoute('test.param');
    }
}
