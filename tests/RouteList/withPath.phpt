<?php

declare(strict_types=1);

use Nette\Routing\RouteList;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list
	->withPath('sup')
		->addRoute('foo', ['route' => 'foo'])
		->withPath('sub')
			->addRoute('bar', ['route' => 'bar'])
		->end()
	->end()
	->withPath('/slash')
		->addRoute('hello', ['route' => 'hello'])
	->end()
	->withPath('slash2/')
		->addRoute('hello2', ['route' => 'hello2']);


testRouteIn($list, '/foo');
testRouteIn($list, '/supfoo');

testRouteIn($list, '/sup/foo', ['route' => 'foo', 'test' => 'testvalue'], '/sup/foo?test=testvalue');

testRouteIn($list, '/bar');
testRouteIn($list, '/sup/bar');

testRouteIn($list, '/sup/sub/bar', ['route' => 'bar', 'test' => 'testvalue'], '/sup/sub/bar?test=testvalue');

testRouteIn($list, '/slash/hello'); // /slash is not allowed

testRouteIn($list, '/slash2/hello2', ['route' => 'hello2', 'test' => 'testvalue'], '/slash2/hello2?test=testvalue');



// trailing slash is optional
$list = new RouteList;
$list
	->withPath('foo')
		->addRoute('', ['route' => 'foo'])
		->withPath('bar')
			->addRoute('', ['route' => 'bar']);

testRouteIn($list, '/foo', ['route' => 'foo', 'test' => 'testvalue'], '/foo/?test=testvalue');
testRouteIn($list, '/foo/', ['route' => 'foo', 'test' => 'testvalue'], '/foo/?test=testvalue');

testRouteIn($list, '/foo/bar', ['route' => 'bar', 'test' => 'testvalue'], '/foo/bar/?test=testvalue');
testRouteIn($list, '/foo/bar/', ['route' => 'bar', 'test' => 'testvalue'], '/foo/bar/?test=testvalue');

testRouteIn($list, '/foobar');
