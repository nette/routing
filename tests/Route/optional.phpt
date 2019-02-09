<?php

/**
 * Test: Nette\Routing\Route with optional sequence.
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('index[.html]', [
]);

testRouteIn($route, '/index.html', [
	'test' => 'testvalue',
], '/index?test=testvalue');

testRouteIn($route, '/index', [
	'test' => 'testvalue',
], '/index?test=testvalue');
