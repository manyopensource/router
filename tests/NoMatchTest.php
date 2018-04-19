<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class NoMatchTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test not found
     *
     * @expectedException Maer\Router\Exceptions\NotFoundException
     */
    public function testNotFound()
    {
        $r = new Router;

        $r->get('/test', function () {
            return 'GET /';
        });

        $r->dispatch('GET', '/foo');
    }

    /**
     * Test method not allowed
     *
     * @expectedException Maer\Router\Exceptions\MethodNotAllowedException
     */
    public function testNotAllowed()
    {
        $r = new Router;

        $r->post('/method-not-allowed', function () {
            return 'GET /';
        });

        $r->dispatch('GET', '/method-not-allowed');
    }

    /**
     * Test multiple matches, all wrong method
     *
     * @expectedException Maer\Router\Exceptions\MethodNotAllowedException
     */
    public function testMultipleMatchesWrongMethods()
    {
        $r = new Router;

        $r->get('/test', function () {
            return "/test";
        });

        $r->post('/test/(:any)?', function ($p = null) {
            return "/test/{$p}";
        });

        $r->put('/test', function ($p = null) {
            return "/test/{$p}";
        });

        $r->dispatch('DELETE', '/test');
    }

}
