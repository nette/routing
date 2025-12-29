<?php

/**
 * Test: Nette\Routing\Route with slash in path.
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('single parameter route handles trailing slash', function () {
	$route = new Route('<param>');

	testRouteIn($route, '/a/', ['param' => 'a', 'test' => 'testvalue'], '/a?test=testvalue');
	testRouteIn($route, '/a//');

	testRouteIn($route, '/a/b');
	Assert::null(testRouteOut($route, ['param' => 'a/b']));
});


test('parameter with .+ pattern matches slashes in path', function () {
	$route = new Route('<param .+>');

	testRouteIn($route, '/a/b', [
		'param' => 'a/b',
		'test' => 'testvalue',
	], '/a/b?test=testvalue');
});


test('multiple consecutive slashes in mask work correctly', function () {
	// This should work - testing it doesn't throw
	$route = new Route('<presenter>//<action>');
	Assert::same('<presenter>//<action>', $route->getMask());
});
