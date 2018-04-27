<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class PrefixTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test prefix
     */
    public function testPrefix()
    {
        $r = new Router;

        $r->group(['prefix' => 'foo'], function ($r) {
            $r->get('bar', function () {
                return '/foo/bar';
            });
        });

        $this->assertEquals('/foo/bar', $r->dispatch('GET', '/foo/bar'));
    }

    /**
     * Test params prefix
     */
    public function testParamsPrefix()
    {
        $r = new Router;

        $r->group(['prefix' => 'foo/(:any)'], function ($r) {
            $r->get('bar', function ($p) {
                return "/foo/{$p}/bar";
            });
        });

        $this->assertEquals('/foo/test/bar', $r->dispatch('GET', '/foo/test/bar'));
    }

    /**
     * Test nested params prefix
     */
    public function testNestedGroupPrefix()
    {
        $r = new Router;

        $r->group(['prefix' => 'foo'], function ($r) {
            $r->group(['prefix' => 'bar'], function ($r) {
                $r->get('/', function () {
                    return "/foo/bar";
                });

                $r->get('/test', function () {
                    return "/foo/bar/test";
                });
            });
        });

        $this->assertEquals('/foo/bar', $r->dispatch('GET', '/foo/bar'));
        $this->assertEquals('/foo/bar/test', $r->dispatch('GET', '/foo/bar/test'));
    }

    /**
     * Test tokens in group prefix
     */
    public function testGroupTokenPrefix()
    {
        $r = new Router;

        $r->group(['prefix' => '/foo/(:any)'], function ($r) {
            $r->get('/', function ($p) {
                return "/foo/{$p}";
            });
        });

        $this->assertEquals('/foo/bar', $r->dispatch('get', '/foo/bar'));
    }

    /**
     * Test tokens in nested groups
     */
    public function testNestedGroupTokenPrefix()
    {
        $r = new Router;

        $r->group(['prefix' => '/foo/(:any)'], function ($r) {
            $r->get('/bar/(:any)', function ($p, $p2) {
                return "/foo/{$p}/bar/{$p2}";
            });
        });

        $this->assertEquals('/foo/123/bar/abc', $r->dispatch('get', '/foo/123/bar/abc'));
    }

    /**
     * Test tokens in deeply nested groups
     */
    public function testDeeplyNestedGroupTokenPrefix()
    {
        $r = new Router;

        $r->group(['prefix' => '/foo/(:any)'], function ($r) {
            $r->group(['prefix' => '/bar/(:any)'], function ($r) {
                $r->get('/hello/(:any)', function ($p, $p2, $p3) {
                    return "/foo/{$p}/bar/{$p2}/hello/{$p3}";
                });
            });
        });

        $r->group(['prefix' => '(:any)'], function ($r) {
            $r->group(['prefix' => '(:any)'], function ($r) {
                $r->get('/', function ($p, $p2) {
                    return "/{$p}/{$p2}";
                });
            });
        });

        $r->group(['prefix' => '(:any)'], function ($r) {
            $r->group(['prefix' => '(:any)'], function ($r) {
                $r->get('/test', function ($p, $p2) {
                    return "/{$p}/{$p2}/test";
                });
            });
        });

        $this->assertEquals('/foo/123/bar/abc/hello/world', $r->dispatch('get', '/foo/123/bar/abc/hello/world'));
        $this->assertEquals('/hello/world', $r->dispatch('get', '/hello/world'));
        $this->assertEquals('/hello/world/test', $r->dispatch('get', '/hello/world/test'));
    }
}
