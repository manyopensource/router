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
        State::set('before', 0);

        $r = new Router;

        // Normal
        $r->filter('test', function () {
            State::set('before', 1);
        });

        $r->get('/test', function () {
            return "test:" . State::get('before');
        }, ['before' => 'test']);

        $this->assertEquals('test:1', $r->dispatch('GET', '/test'));
    }

    /**
     * Test before filters that return a value
     */
    public function testBeforeFilterReturn()
    {
        $r = new Router;

        // Break
        $r->filter('test', function () {
            return 'no';
        });

        $r->get('/test', function () {
            return "yes";
        }, ['before' => 'test']);

        $this->assertEquals('no', $r->dispatch('GET', '/test'));
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

        $r->get('/test', function () {
            return implode(',', State::get('before.multiple'));
        }, ['before' => 'first|second']);

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

        $r->get('/test-after', function () {
            return "/test";
        }, ['after' => 'test']);

        $response = $r->dispatch('GET', '/test-after');

        $this->assertEquals('/test', $response);
        $this->assertEquals('1', State::get('after'));
    }

    /**
     * Test before filters that return a value
     */
    public function testAfterFilterReturn()
    {
        $r = new Router;

        // Break
        $r->filter('test', function ($response) {
            return 'no' . $response;
        });

        $r->get('/test-after-return', function () {
            return "yes";
        }, ['after' => 'test']);

        $this->assertEquals('noyes', $r->dispatch('GET', '/test-after-return'));
    }

    /**
     * Test multiple filter
     */
    public function testMultipleAfterFilters()
    {
        $r = new Router;

        $r->filter('first', function ($response) {
            $response .= 1;
            return $response;
        });

        $r->filter('second', function ($response) {
            $response .= 2;
            return $response;
        });

        $r->get('/test', function () {
            return '';
        }, ['after' => 'first|second']);

        $this->assertEquals('12', $r->dispatch('GET', '/test'));
    }

    /**
     * Test before filter
     */
    public function testGroupBeforeFilter()
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
    public function testMultipleGroupBeforeFilters()
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
    public function testGroupAfterFilter()
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
    public function testMultipleGroupAfterFilters()
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
