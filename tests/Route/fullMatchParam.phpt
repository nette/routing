<?php

/**
 * Test: Nette\Routing\Route and full match parameter.
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<param .+>');

testRouteIn($route, '/one', [
	'param' => 'one',
	'test' => 'testvalue',
], '/one?test=testvalue');

testRouteIn($route, '/');
