<?php

/**
 * Test: Nette\Routing\Route auto-optional sequence.
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action=default>/static/<id=0>');

testRouteIn($route, '/presenter/action/12/any');

testRouteIn($route, '/presenter/action/12');

testRouteIn($route, '/presenter/action/static/12', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '12',
	'test' => 'testvalue',
], '/presenter/action/static/12?test=testvalue');

testRouteIn($route, '/presenter/action/static', [
	'presenter' => 'presenter',
	'id' => '0',
	'action' => 'action',
	'test' => 'testvalue',
], '/presenter/action/static/?test=testvalue');

testRouteIn($route, '/presenter/action/');

testRouteIn($route, '/presenter/action');

testRouteIn($route, '/presenter/', [
	'presenter' => 'presenter',
	'id' => '0',
	'action' => 'default',
	'test' => 'testvalue',
], '/presenter/?test=testvalue');

testRouteIn($route, '/presenter', [
	'presenter' => 'presenter',
	'id' => '0',
	'action' => 'default',
	'test' => 'testvalue',
], '/presenter/?test=testvalue');

testRouteIn($route, '/');
