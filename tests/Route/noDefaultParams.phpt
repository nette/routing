<?php

/**
 * Test: Nette\Application\Routers\Route with NoDefaultParams
 */

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action>/<extra>', [
]);

testRouteIn($route, '/presenter/action/12', 'Presenter', [
	'action' => 'action',
	'extra' => '12',
	'test' => 'testvalue',
], null);
