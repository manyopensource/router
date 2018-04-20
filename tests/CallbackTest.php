<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class CallbackTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test closure callbacks
     */
    public function testClosure()
    {
        $r = new Router;

        $r->get('/', function () {
            return 'get:home';
        });

        $r->post('/test', function () {
            return 'post:test';
        });

        $this->assertEquals('get:home',  $r->dispatch('get', '/'),      'Closure get:home');
        $this->assertEquals('post:test', $r->dispatch('post', '/test'), 'Closure post:test');
    }

    /**
     * Test static class callback
     */
    public function testStatic()
    {
        $r = new Router;

        $r->get('/test', 'Controller::staticIndex');

        $this->assertEquals('static:foo',  $r->dispatch('get', '/test'));
    }

    /**
     * Test instantiated class callback
     */
    public function testInstance()
    {
        $r = new Router;

        $r->get('/test', ['Controller','index']);
        $r->get('/test2', 'Controller@index');

        $this->assertEquals('instance:foo',  $r->dispatch('get', '/test'), 'Comma separated');
        $this->assertEquals('instance:foo',  $r->dispatch('get', '/test2'), '@ separated');
    }

    /**
     * Test named function callback
     */
    public function testNamedFunction()
    {
        $r = new Router;

        $r->get('/test', 'controllerFunc');

        $this->assertEquals('func:foo',  $r->dispatch('get', '/test'), 'func');
    }

    /**
     * Test invalid class
     *
     * @expectedException Maer\Router\Exceptions\ControllerNotFoundException
     */
    public function testMissingClass()
    {
        $r = new Router;

        $r->get('/test', 'InvalidController@test');

        $r->dispatch('get', '/test');
    }

    /**
     * Test invalid named function
     *
     * @expectedException Maer\Router\Exceptions\ControllerNotFoundException
     */
    public function testMissingNamedFunction()
    {
        $r = new Router;

        $r->get('/test', 'controllerFuncNonExisting');

        $r->dispatch('get', '/test');
    }

    /**
     * Test invalid invalid static controller
     *
     * @expectedException Maer\Router\Exceptions\ControllerNotFoundException
     */
    public function testMissingStaticController()
    {
        $r = new Router;

        $r->get('/test', 'NonExisting::controller');

        $r->dispatch('get', '/test');
    }
}
