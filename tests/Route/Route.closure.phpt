<?php

/**
 * Test: Nette\Application\Routers\Route with closure.
 */

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';


$closure = function () {};
$route = new Route('<id>', $closure);

testRouteIn($route, '/12', 'Nette:Micro', [
	'id' => '12',
	'test' => 'testvalue',
	'callback' => $closure,
], '/12?test=testvalue');
