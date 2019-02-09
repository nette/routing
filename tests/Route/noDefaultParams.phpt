<?php

/**
 * Test: Nette\Routing\Route with NoDefaultParams
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action>/<extra>', [
]);

testRouteIn($route, '/presenter/action/12', [
	'presenter' => 'presenter',
	'action' => 'action',
	'extra' => '12',
	'test' => 'testvalue',
], null);
