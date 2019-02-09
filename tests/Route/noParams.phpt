<?php

/**
 * Test: Nette\Application\Routers\Route default usage.
 */

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('index.php', [
	'action' => 'default',
]);

testRouteIn($route, '/index.php', 'querypresenter', [
	'action' => 'default',
	'test' => 'testvalue',
], '/index.php?test=testvalue&presenter=querypresenter');

testRouteIn($route, '/');
