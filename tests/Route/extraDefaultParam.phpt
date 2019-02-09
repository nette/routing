<?php

/**
 * Test: Nette\Routing\Route with ExtraDefaultParam
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action>/<id \d{1,3}>/', [
	'extra' => null,
]);

testRouteIn($route, '/presenter/action/12/any');

testRouteIn($route, '/presenter/action/12', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '12',
	'extra' => null,
	'test' => 'testvalue',
], '/presenter/action/12/?test=testvalue');

testRouteIn($route, '/presenter/action/1234');

testRouteIn($route, '/presenter/action/');

testRouteIn($route, '/presenter');

testRouteIn($route, '/');
