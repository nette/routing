<?php

/**
 * Test: Nette\Routing\Route: required parameter with default value
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<default=123>/<required>', [
	'action' => 'default',
]);

testRouteIn($route, '/presenter/');
testRouteIn($route, '/presenter/abc');
testRouteIn($route, '/presenter/abc/');

testRouteIn($route, '/presenter/abc/xyy', [
	'presenter' => 'presenter',
	'default' => 'abc',
	'action' => 'default',
	'test' => 'testvalue',
	'required' => 'xyy',
], '/presenter/abc/xyy?test=testvalue');


Assert::null(testRouteOut($route, ['presenter' => 'homepage']));
Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'default' => 'abc']));

Assert::same(
	'http://example.com/homepage/123/xyz',
	testRouteOut($route, ['presenter' => 'homepage', 'required' => 'xyz']),
);

Assert::same(
	'http://example.com/homepage/abc/xyz',
	testRouteOut($route, ['presenter' => 'homepage', 'required' => 'xyz', 'default' => 'abc']),
);
