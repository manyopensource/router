<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class TokenTest extends PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $r = new Router;

        $r->add('GET', '/test/(:num)', function ($p) {
            return "get:test:{$p}:int";
        });

        $r->add('GET', '/test/(:alpha)', function ($p) {
            return "get:test:{$p}:alpha";
        });

        $r->add('GET', '/any/(:any)', function ($p) {
            return "get:any:{$p}";
        });

        $r->add('GET', '/all/(:all)', function ($p) {
            return "get:all:{$p}";
        });

        $this->assertEquals('get:test:123:int',   $r->dispatch('get', '/test/123'), 'Closure get:test:123:int');
        $this->assertEquals('get:test:abc:alpha', $r->dispatch('get', '/test/abc'), 'Closure get:test:abc:alpha');

        $this->assertEquals('get:any:12ab',    $r->dispatch('get', '/any/12ab'),    'Closure get:any:12ab');
        $this->assertEquals('get:all:123/abc', $r->dispatch('get', '/all/123/abc'), 'Closure get:all:123/abc');
    }

    public function testCustom()
    {
        $r = new Router;

        $r->addToken('test', '\d{3}');

        $r->add('GET', '/custom/(:test)', function ($p) {
            return "get:custom:{$p}";
        });

        // Make sure the custom doesn't match this one
        $r->add('GET', '/custom/(:num)', function ($p) {
            return "get:test:{$p}";
        });

        $this->assertEquals('get:custom:123',  $r->dispatch('get', '/custom/123'), 'Closure get:custom:123');
        $this->assertEquals('get:test:1234', $r->dispatch('get', '/custom/1234'),  'Closure get:test:1234');
    }
}
