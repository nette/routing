<?php

/**
 * Test: Nette\Routing\Route with WithUserClassAlt
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<id>', [
	'id' => [
		Route::Pattern => '\d{1,3}',
	],
]);

testRouteIn($route, '/presenter/12/', [
	'presenter' => 'presenter',
	'id' => '12',
	'test' => 'testvalue',
], '/presenter/12?test=testvalue');

testRouteIn($route, '/presenter/1234');

testRouteIn($route, '/presenter/');
