<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class GroupTest extends PHPUnit_Framework_TestCase
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

    /**
     * Test before filter
     */
    public function testBeforeFilter()
    {
        State::set('before', 0);

        $r = new Router;

        // Normal
        $r->filter('test', function () {
            State::set('before', 1);
        });

        $r->group(['before' => 'test'], function ($r) {
            $r->get('/test', function () {
                return "test:" . State::get('before');
            });
        });

        $this->assertEquals('test:1', $r->dispatch('GET', '/test'));
    }

    /**
     * Test multiple filter
     */
    public function testMultipleBeforeFilters()
    {
        State::set('before.multiple', []);

        $r = new Router;

        $r->filter('first', function () {
            State::push('before.multiple', 1);
        });

        $r->filter('second', function () {
            State::push('before.multiple', 2);
        });

        $r->group(['before' => 'first'], function ($r) {
            $r->group(['before' => 'second'], function ($r) {
                $r->get('/test', function () {
                    return implode(',', State::get('before.multiple'));
                });
            });
        });

        $this->assertEquals('1,2', $r->dispatch('GET', '/test'));
    }

    /**
     * Test after filter
     */
    public function testAfterFilter()
    {
        State::set('after', 0);

        $r = new Router;

        // Normal
        $r->filter('test', function () {
            State::set('after', 1);
        });

        $r->group(['after' => 'test'], function ($r) {
            $r->get('/test', function () {
                return 'foo';
            });
        });

        $this->assertEquals('foo', $r->dispatch('GET', '/test'));
        $this->assertEquals(1, State::get('after'));
    }

    /**
     * Test multiple filter
     */
    public function testMultipleAfterFilters()
    {
        State::set('after.multiple', []);

        $r = new Router;

        $r->filter('first', function () {
            State::push('after.multiple', 1);
        });

        $r->filter('second', function () {
            State::push('after.multiple', 2);
        });

        $r->group(['after' => 'first'], function ($r) {
            $r->group(['after' => 'second'], function ($r) {
                $r->get('/test', function () {
                    return 'foo';
                });
            });
        });

        $this->assertEquals('foo', $r->dispatch('GET', '/test'));
        $this->assertEquals('1,2', implode(',', State::get('after.multiple')));
    }
}
