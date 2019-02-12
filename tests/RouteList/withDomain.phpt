<?php

declare(strict_types=1);

use Nette\Routing\RouteList;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list
	->withDomain('example.com')
		->addRoute('foo', ['route' => 'foo'])
	->end()
	->withDomain('example.org')
		->addRoute('bar', ['route' => 'bar'])
	->end()
	->withDomain('example.%tld%')
		->addRoute('hello', ['route' => 'hello'])
	->end();


testRouteIn($list, '/foo', ['route' => 'foo', 'test' => 'testvalue'], '/foo?test=testvalue');

testRouteIn($list, '/bar');

testRouteIn($list, '/hello', ['route' => 'hello', 'test' => 'testvalue'], '/hello?test=testvalue');
