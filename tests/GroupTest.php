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
}
