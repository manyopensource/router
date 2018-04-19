<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class DispatchTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test direct match
     */
    public function testDispatch()
    {
        $r = new Router;

        $r->get('/', function () {
            return 'GET /';
        });

        $r->post('/test', function () {
            return 'POST /test';
        });

        $this->assertEquals('GET /', $r->dispatch('GET', '/'));
        $this->assertEquals('POST /test', $r->dispatch('POST', '/test'));
    }

    /**
     * Test parameters
     */
    public function testDispatchParameters()
    {
        $r = new Router;

        // Required params
        $r->get('/test/(:any)', function ($p) {
            return "GET /test/{$p}";
        });

        // Optional params
        $r->get('/test2/(:any)/(:any)?', function ($p, $p2 = null) {
            return "GET /test2/{$p}/{$p2}";
        });

        // Optional params inline
        $r->get('/test3/(:any)/(:any)?/test', function ($p, $p2 = null) {
            return $p2
                ? "GET /test3/{$p}/{$p2}/test"
                : "GET /test3/{$p}/test";
        });

        $this->assertEquals('GET /test/foo', $r->dispatch('GET', '/test/foo'));
        $this->assertEquals('GET /test2/foo/bar', $r->dispatch('GET', '/test2/foo/bar'));

        $this->assertEquals('GET /test3/foo/test', $r->dispatch('GET', '/test3/foo/test'));
        $this->assertEquals('GET /test3/foo/bar/test', $r->dispatch('GET', '/test3/foo/bar/test'));
    }

    /**
     * Test multiple matches
     */
    public function testMultipleMatchesDifferentMethods()
    {
        $r = new Router;

        // Correct match, wrong method
        $r->post('/test', function () {
            return "POST /test";
        });

        // Correct match, wrong method
        $r->put('/test', function () {
            return "PUT /test";
        });

        // Correct match with optional param, correct method
        $r->get('/test/(:any)?', function ($p = null) {
            return "GET /test/{$p}";
        });

        $this->assertEquals('GET /test/', $r->dispatch('GET', '/test'));
        $this->assertEquals('POST /test', $r->dispatch('POST', '/test'));
        $this->assertEquals('PUT /test', $r->dispatch('PUT', '/test'));
    }

    /**
     * Test any
     */
    public function testAny()
    {
        $r = new Router;

        // Correct match, wrong method
        $r->any('/test', function () {
            return "/test";
        });

        $this->assertEquals('/test', $r->dispatch('GET', '/test'));
        $this->assertEquals('/test', $r->dispatch('POST', '/test'));
        $this->assertEquals('/test', $r->dispatch('PUT', '/test'));
        $this->assertEquals('/test', $r->dispatch('DELETE', '/test'));
    }

}
