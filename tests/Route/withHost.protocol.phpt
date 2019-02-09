<?php

/**
 * Test: Nette\Routing\Route with host & protocol
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('http://<host>.<domain>/<path>');

testRouteIn($route, '/abc', [
	'host' => 'example',
	'domain' => 'com',
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


$route = new Route('https://<host>.<domain>/<path>');

testRouteIn($route, '/abc', [
	'host' => 'example',
	'domain' => 'com',
	'path' => 'abc',
	'test' => 'testvalue',
], 'https://example.com/abc?test=testvalue');
