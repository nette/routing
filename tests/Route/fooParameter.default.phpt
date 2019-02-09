<?php

/**
 * Test: Nette\Routing\Route with FooParameter
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('index<?.xml \.html?|\.php|>/', [
	'presenter' => 'defaultPresenter',
]);

testRouteIn($route, '/index.');

testRouteIn($route, '/index.xml', [
	'presenter' => 'defaultPresenter',
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index.php', [
	'presenter' => 'defaultPresenter',
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index.htm', [
	'presenter' => 'defaultPresenter',
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index', [
	'presenter' => 'defaultPresenter',
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');
