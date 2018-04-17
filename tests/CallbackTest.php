<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class CallbackTest extends PHPUnit_Framework_TestCase
{
    public function testClosure()
    {
        $r = new Router;

        $r->add('GET',  '/',      function () { return 'get:home'; });
        $r->add('GET',  '/test',  function () { return 'get:test'; });
        $r->add('POST', '/',     function () { return 'post:home'; });
        $r->add('POST', '/test', function () { return 'post:test'; });

        $this->assertEquals('get:home',  $r->dispatch('get', '/'),      'Closure get:home');
        $this->assertEquals('get:test',  $r->dispatch('get', '/test'),  'Closure get:test');
        $this->assertEquals('post:home', $r->dispatch('post', '/'),     'Closure post:home');
        $this->assertEquals('post:test', $r->dispatch('post', '/test'), 'Closure post:test');
    }
}
