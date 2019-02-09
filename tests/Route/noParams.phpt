<?php

/**
 * Test: Nette\Routing\Route default usage.
 */

declare(strict_types=1);

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
