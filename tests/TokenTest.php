<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class TokenTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test all
     */
    public function testAll()
    {
        $r = new Router;

        $r->get('/r/(:all)', function ($p) {
            return "r:{$p}";
        });

        $this->assertEquals('r:foo/bar', $r->dispatch('get', '/r/foo/bar'));
    }

    /**
     * Test alphanum
     */
    public function testAlphanum()
    {
        $r = new Router;

        $r->get('/r/(:alphanum)', function ($p) {
            return "r:{$p}";
        });

        $r->get('/r/(:any)', function ($p) {
            return "r:backup";
        });


        $this->assertEquals('r:abc123', $r->dispatch('get', '/r/abc123'));
        $this->assertEquals('r:backup', $r->dispatch('get', '/r/abc-123'));
    }

    /**
     * Test alpha
     */
    public function testAlpha()
    {
        $r = new Router;

        $r->get('/r/(:alpha)', function ($p) {
            return "r:{$p}";
        });

        $r->get('/r/(:any)', function ($p) {
            return "r:backup";
        });


        $this->assertEquals('r:abc', $r->dispatch('get', '/r/abc'));
        $this->assertEquals('r:backup', $r->dispatch('get', '/r/123'));
    }

    /**
     * Test num
     */
    public function testNum()
    {
        $r = new Router;

        $r->get('/r/(:num)', function ($p) {
            return "r:{$p}";
        });

        $r->get('/r/(:any)', function ($p) {
            return "r:backup";
        });


        $this->assertEquals('r:123', $r->dispatch('get', '/r/123'));
        $this->assertEquals('r:-123', $r->dispatch('get', '/r/-123'));
        $this->assertEquals('r:1.23', $r->dispatch('get', '/r/1.23'));
        $this->assertEquals('r:backup', $r->dispatch('get', '/r/abc'));
    }

    /**
     * Test custom token
     */
    public function testCustom()
    {
        $r = new Router;

        $r->addToken('bar', 'bar');
        $r->addToken('four', '[a-z]{4}');

        $r->get('/foo/(:bar)', function ($p) {
            return "bar:/foo/{$p}";
        });

        $r->get('/foo/(:four)', function ($p) {
            return "four:/foo/{$p}";
        });

        $r->get('/foo/(:any)', function ($p) {
            return "any:/foo/{$p}";
        });

        $this->assertEquals('bar:/foo/bar', $r->dispatch('get', '/foo/bar'));
        $this->assertEquals('any:/foo/abc', $r->dispatch('get', '/foo/abc'));
        $this->assertEquals('four:/foo/abcd', $r->dispatch('get', '/foo/abcd'));
    }
}
