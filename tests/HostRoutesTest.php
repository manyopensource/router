<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class HostRoutesTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test specific host
     */
    public function testSpecificHost()
    {
        $r = new Router;

        $r->get('/', function () {
            return 'home:foo.com';
        }, ['host' => 'foo.com']);

        $this->assertEquals('home:foo.com', $r->dispatch('get', '/', 'foo.com'));
    }

    /**
     * Test invalid specific host
     *
     * @expectedException Maer\Router\Exceptions\NotFoundException
     */
    public function testInvalidSpecificHost()
    {
        $r = new Router;

        $r->get('/', function () {
            return 'home:foo.com';
        }, ['host' => 'foo.com']);

        $this->assertEquals('home:foo.com', $r->dispatch('get', '/'));
    }

    /**
     * Test same route different hosts
     */
    public function testSameRouteDifferentHosts()
    {
        $r = new Router;

        $r->get('/', function () {
            return 'home:default';
        });

        $r->get('/', function () {
            return 'home:foo.com';
        }, ['host' => 'foo.com']);

        $r->get('/', function () {
            return 'home:example.com';
        }, ['host' => 'example.com']);

        $this->assertEquals('home:default', $r->dispatch('get', '/'));
        $this->assertEquals('home:foo.com', $r->dispatch('get', '/', 'foo.com'));
        $this->assertEquals('home:example.com', $r->dispatch('get', '/', 'example.com'));
    }

    /**
     * Test hosts containing tokens
     */
    public function testTokenHost()
    {
        $r = new Router;

        // Three alpha characters
        $r->addToken('threealpha', '[a-z]{3}');

        $r->get('/', function () {
            return 'home:default';
        });

        $r->get('/', function () {
            return 'home:foo.com';
        }, ['host' => '(:threealpha).com']);

        $r->get('/', function () {
            return 'home:example.com';
        }, ['host' => 'example.com']);

        $this->assertEquals('home:default', $r->dispatch('get', '/'));
        $this->assertEquals('home:foo.com', $r->dispatch('get', '/', 'foo.com'));
        $this->assertEquals('home:example.com', $r->dispatch('get', '/', 'example.com'));
    }

    /**
     * Test grouped host
     */
    public function testSameRouteDifferentHostsInGroups()
    {
        $r = new Router;

        $r->group([], function ($r) {
            $r->get('/', function () {
                return 'home:default';
            });
        });

        $r->group(['host' => 'foo.com'], function ($r) {
            $r->get('/', function () {
                return 'home:foo.com';
            });
        });

        $r->group(['host' => 'example.com'], function ($r) {
            $r->get('/', function () {
                return 'home:example.com';
            });
        });

        $this->assertEquals('home:default', $r->dispatch('get', '/'));
        $this->assertEquals('home:foo.com', $r->dispatch('get', '/', 'foo.com'));
        $this->assertEquals('home:example.com', $r->dispatch('get', '/', 'example.com'));
    }
}
