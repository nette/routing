<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route default usage.
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('index.php', [
	'action' => 'default',
]);

testRouteIn($route, '/index.php', [
	'action' => 'default',
	'test' => 'testvalue',
], '/index.php?test=testvalue');

testRouteIn($route, '/');
