<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with 'required' optional sequence.
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('index[!.html]', [
]);

testRouteIn($route, '/index.html', [
	'test' => 'testvalue',
], '/index.html?test=testvalue');

testRouteIn($route, '/index', [
	'test' => 'testvalue',
], '/index.html?test=testvalue');
