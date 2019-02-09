<?php

/**
 * Test: Nette\Routing\Route auto-optional sequence.
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action=default>[/<id>]');

testRouteIn($route, '/presenter/action/12/any');

testRouteIn($route, '/presenter/action/12', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '12',
	'test' => 'testvalue',
], '/presenter/action/12?test=testvalue');

testRouteIn($route, '/presenter/action/', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => null,
	'test' => 'testvalue',
], '/presenter/action?test=testvalue');

testRouteIn($route, '/presenter/action', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => null,
	'test' => 'testvalue',
], '/presenter/action?test=testvalue');

testRouteIn($route, '/presenter/', [
	'presenter' => 'presenter',
	'id' => null,
	'action' => 'default',
	'test' => 'testvalue',
], '/presenter/?test=testvalue');

testRouteIn($route, '/presenter', [
	'presenter' => 'presenter',
	'id' => null,
	'action' => 'default',
	'test' => 'testvalue',
], '/presenter/?test=testvalue');

testRouteIn($route, '/');
