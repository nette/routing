<?php

/**
 * Test: Nette\Routing\Route with WithParamsInQuery
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<action> ? <presenter>', [
	'presenter' => 'default',
	'action' => 'default',
]);

testRouteIn($route, '/action/', [
	'presenter' => 'default',
	'action' => 'action',
	'test' => 'testvalue',
], '/action?test=testvalue');

testRouteIn($route, '/', [
	'action' => 'default',
	'presenter' => 'default',
	'test' => 'testvalue',
], '/?test=testvalue');
