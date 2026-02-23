<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route and full match parameter.
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<param .+>');

testRouteIn($route, '/one', [
	'param' => 'one',
	'test' => 'testvalue',
], '/one?test=testvalue');

testRouteIn($route, '/');
