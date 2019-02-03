<?php

/**
 * Test: Nette\Application\Routers\Route with WithHost
 */

declare(strict_types=1);

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('//<host>.<domain>/<path>');

testRouteIn($route, '/abc', [
	'host' => 'example',
	'domain' => 'com',
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');
