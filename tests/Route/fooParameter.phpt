<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with FooParameter
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('index<?.xml>/', [
	'presenter' => 'defaultPresenter',
]);


testRouteIn($route, '/index.');

testRouteIn($route, '/index.xml', [
	'presenter' => 'defaultPresenter',
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index.php');

testRouteIn($route, '/index');
