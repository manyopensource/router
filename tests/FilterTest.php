<?php

use Maer\Router\Router;

/**
 * @coversDefaultClass \Maer\Router\Router
 */
class FilterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test before filter
     */
    public function testBeforeFilter()
    {
        $test = "unchanged";

        $r = new Router;

        // Normal
        $r->filter('test', function () {
            global $test;
            $test = 'changed';
        });

        $r->group(['before' => 'test'], function ($r) {
            $r->get('/test', function () {
                global $test;
                return "test:{$test}";
            });
        });

        $this->assertEquals('test:changed', $r->dispatch('GET', '/test'));
    }

    /**
     * Test before filters that return a value
     */
    public function testBeforeFilterReturn()
    {
        $r = new Router;

        // Break
        $r->filter('test2', function () {
            return 'no';
        });

        $r->group(['before' => 'test2'], function ($r) {
            $r->get('/test', function () {
                return "yes";
            });
        });

        $this->assertEquals('no', $r->dispatch('GET', '/test'));
    }

    /**
     * Test multiple filter
     */
    public function testMultipleBeforeFilters()
    {
        $test = [];

        $r = new Router;

        $r->filter('first', function () {
            global $test;
            $test[] = 1;
        });

        $r->filter('second', function () {
            global $test;
            $test[] = 2;
        });

        $r->group(['before' => 'first|second'], function ($r) {
            $r->get('/test', function () {
                global $test;
                return implode(',', $test);
            });
        });

        $this->assertEquals('1,2', $r->dispatch('GET', '/test'));
    }

    /**
     * Test after filter
     */
    public function xtestAfterFilter()
    {
        $test2 = "unchanged";

        $r = new Router;

        // Normal
        $r->filter('test', function () {
            global $test2;
            $test2 = 'changed';
        });

        $r->group(['after' => 'test'], function ($r) {
            $r->get('/test', function () {
                return "/test";
            });
        });

        $response = $r->dispatch('GET', '/test');

        global $test2;
        $this->assertEquals('/test', $response);
        $this->assertEquals('changed', $test2);
    }

    /**
     * Test before filters that return a value
     */
    public function xtestAfterFilterReturn()
    {
        $r = new Router;

        // Break
        $r->filter('test2', function ($response = null) {
            return 'no';
        });

        $r->group(['before' => 'test2'], function ($r) {
            $r->get('/test', function () {
                return "yes";
            });
        });

        $this->assertEquals('no', $r->dispatch('GET', '/test'));
    }

    /**
     * Test multiple filter
     */
    public function xtestMultipleAfterFilters()
    {
        $test3 = [];

        $r = new Router;

        $r->filter('first', function () {
            global $test3;
            $test3[] = 1;
        });

        $r->filter('second', function () {
            global $test3;
            $test3[] = 2;
        });

        $r->group(['before' => 'first|second'], function ($r) {
            $r->get('/test', function () {
                global $test3;
                return implode(',', $test3);
            });
        });

        $this->assertEquals('1,2', $r->dispatch('GET', '/test'));
    }
}
