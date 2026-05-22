<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route::setStrictDefaults() rejects URLs with redundant default values.
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action>', [
	'presenter' => 'home',
	'action' => 'default',
]);
$route->setStrictDefaults();

testRouteIn($route, '/foo/bar', [
	'presenter' => 'foo',
	'action' => 'bar',
	'test' => 'testvalue',
], '/foo/bar?test=testvalue');

testRouteIn($route, '/home/bar', [
	'presenter' => 'home',
	'action' => 'bar',
	'test' => 'testvalue',
], '/home/bar?test=testvalue');

testRouteIn($route, '/foo/default', null);
testRouteIn($route, '/home/default', null);
testRouteIn($route, '/home', null);

// disabling restores the default permissive behavior
$route->setStrictDefaults(false);
testRouteIn($route, '/foo/default', [
	'presenter' => 'foo',
	'action' => 'default',
	'test' => 'testvalue',
], '/foo/?test=testvalue');
