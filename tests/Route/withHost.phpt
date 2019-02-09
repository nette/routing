<?php

/**
 * Test: Nette\Application\Routers\Route with WithHost
 */

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('//<host>.<domain>/<path>', [
	'presenter' => 'Default',
	'action' => 'default',
]);

testRouteIn($route, '/abc', 'Default', [
	'host' => 'example',
	'domain' => 'com',
	'path' => 'abc',
	'action' => 'default',
	'test' => 'testvalue',
], '/abc?test=testvalue');
