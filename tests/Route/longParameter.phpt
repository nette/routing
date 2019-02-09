<?php

/**
 * Test: Nette\Application\Routers\Route with LongParameter
 */

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<parameter-longer-than-32-characters>', [
	'presenter' => 'Presenter',
]);

testRouteIn($route, '/any', 'Presenter', [
	'parameter-longer-than-32-characters' => 'any',
	'test' => 'testvalue',
], '/any?test=testvalue');
