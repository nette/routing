<?php

/**
 * Test: Nette\Routing\Route with CombinedUrlParam
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('extra<presenter>/<action>', [
	'presenter' => 'default',
	'action' => 'default',
]);


testRouteIn($route, '/presenter/action/');

testRouteIn($route, '/extrapresenter/action/', [
	'presenter' => 'presenter',
	'action' => 'action',
	'test' => 'testvalue',
], '/extrapresenter/action?test=testvalue');

testRouteIn($route, '/extradefault/default/', [
	'presenter' => 'default',
	'action' => 'default',
	'test' => 'testvalue',
], '/extra?test=testvalue');

testRouteIn($route, '/extra', [
	'presenter' => 'default',
	'action' => 'default',
	'test' => 'testvalue',
], '/extra?test=testvalue');

testRouteIn($route, '/');
