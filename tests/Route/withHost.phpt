<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with WithHost
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('//<host>.<domain>/<path>');

testRouteIn($route, '/abc', [
	'host' => 'example',
	'domain' => 'com',
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');
