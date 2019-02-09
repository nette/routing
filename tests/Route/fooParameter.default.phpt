<?php

/**
 * Test: Nette\Application\Routers\Route with FooParameter
 */

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('index<?.xml \.html?|\.php|>/', [
	'presenter' => 'DefaultPresenter',
]);

testRouteIn($route, '/index.');

testRouteIn($route, '/index.xml', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index.php', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index.htm', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');
