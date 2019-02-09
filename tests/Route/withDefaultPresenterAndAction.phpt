<?php

/**
 * Test: Nette\Application\Routers\Route with WithDefaultPresenterAndAction
 */

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action>', [
	'presenter' => 'Default',
	'action' => 'default',
]);

testRouteIn($route, '/presenter/action/', 'Presenter', [
	'action' => 'action',
	'test' => 'testvalue',
], '/presenter/action?test=testvalue');

testRouteIn($route, '/default/default/', 'Default', [
	'action' => 'default',
	'test' => 'testvalue',
], '/?test=testvalue');

testRouteIn($route, '/presenter', 'Presenter', [
	'action' => 'default',
	'test' => 'testvalue',
], '/presenter/?test=testvalue');

testRouteIn($route, '/', 'Default', [
	'action' => 'default',
	'test' => 'testvalue',
], '/?test=testvalue');
