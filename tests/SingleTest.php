<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class SingleTest extends PHPUnit_Framework_TestCase
{
    public function testSomething()
    {
        $r = new Router;
        $r->get('/test/(:alpha)?', function () {
            return 'GET /test';
        });

        $r->post('/test', function () {
            return 'POST /test';
        });

        $this->assertEquals('GET /test', $r->dispatch('GET', '/test'));
        $this->assertEquals('GET /test', $r->dispatch('GET', '/test/hello'));
        #$this->assertEquals('POST /test', $r->dispatch('POST', '/test'));
    }
}
