<?php

/**
 * Test: Nette\Routing\Route with optional sequence precedence.
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('[<one>/][<two>]', [
]);

testRouteIn($route, '/one', [
	'one' => 'one',
	'two' => null,
	'test' => 'testvalue',
], '/one/?test=testvalue');

$route = new Route('[<one>/]<two>', [
	'two' => null,
]);

testRouteIn($route, '/one', [
	'one' => 'one',
	'two' => null,
	'test' => 'testvalue',
], '/one/?test=testvalue');
