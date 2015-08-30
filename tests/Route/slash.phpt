<?php

/**
 * Test: Nette\Routing\Route with slash in path.
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('<param>');

testRouteIn($route, '/a/b');
Assert::null(testRouteOut($route, ['param' => 'a/b']));


$route = new Route('<param .+>');

testRouteIn($route, '/a/b', [
	'param' => 'a/b',
	'test' => 'testvalue',
], '/a/b?test=testvalue');
