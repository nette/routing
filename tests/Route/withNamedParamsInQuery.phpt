<?php

/**
 * Test: Nette\Routing\Route with WithNamedParamsInQuery
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('?action=<presenter> & act=<action [a-z]+>', [
	'presenter' => 'default',
	'action' => 'default',
]);

testRouteIn($route, '/?act=action', [
	'presenter' => 'default',
	'action' => 'action',
	'test' => 'testvalue',
], '/?act=action&test=testvalue');

testRouteIn($route, '/?act=default', [
	'action' => 'default',
	'presenter' => 'default',
	'test' => 'testvalue',
], '/?test=testvalue');
