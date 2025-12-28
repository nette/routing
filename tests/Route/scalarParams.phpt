<?php

/**
 * Test: Nette\Routing\Route with scalar params
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('required parameter without default - null and empty string fail', function () {
	$route = new Route('<presenter>/<param>', [
	]);

	Assert::same(
		'http://example.com/homepage/12',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12]),
	);

	Assert::same(
		'http://example.com/homepage/12.1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12.1]),
	);

	Assert::same(
		'http://example.com/homepage/0',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => false]),
	);

	Assert::same(
		'http://example.com/homepage/1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => true]),
	);

	Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'param' => null]));
	Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'param' => '']));
});


test('parameter with empty string default - null and empty string omit value', function () {
	$route = new Route('<presenter>/<param>', [
		'param' => '',
	]);

	Assert::same(
		'http://example.com/homepage/12',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12]),
	);

	Assert::same(
		'http://example.com/homepage/12.1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12.1]),
	);

	Assert::same(
		'http://example.com/homepage/0',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => false]),
	);

	Assert::same(
		'http://example.com/homepage/1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => true]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => null]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => '']),
	);
});


test('parameter with int default - matching value omitted, empty string fails', function () {
	$route = new Route('<presenter>/<param>', [
		'param' => 12,
	]);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12]),
	);

	Assert::same(
		'http://example.com/homepage/12.1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12.1]),
	);

	Assert::same(
		'http://example.com/homepage/0',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => false]),
	);

	Assert::same(
		'http://example.com/homepage/1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => true]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => null]),
	);

	Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'param' => '']));
});


test('parameter with float default - matching value omitted, empty string fails', function () {
	$route = new Route('<presenter>/<param>', [
		'param' => 12.1,
	]);

	Assert::same(
		'http://example.com/homepage/12',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12.1]),
	);

	Assert::same(
		'http://example.com/homepage/0',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => false]),
	);

	Assert::same(
		'http://example.com/homepage/1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => true]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => null]),
	);

	Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'param' => '']));
});


test('parameter with true default - true and null omitted, empty string fails', function () {
	$route = new Route('<presenter>/<param>', [
		'param' => true,
	]);

	Assert::same(
		'http://example.com/homepage/12',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12]),
	);

	Assert::same(
		'http://example.com/homepage/12.1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12.1]),
	);

	Assert::same(
		'http://example.com/homepage/0',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => false]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => true]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => null]),
	);

	Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'param' => '']));
});


test('parameter with false default - false and null omitted, empty string fails', function () {
	$route = new Route('<presenter>/<param>', [
		'param' => false,
	]);

	Assert::same(
		'http://example.com/homepage/12',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12]),
	);

	Assert::same(
		'http://example.com/homepage/12.1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12.1]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => false]),
	);

	Assert::same(
		'http://example.com/homepage/1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => true]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => null]),
	);

	Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'param' => '']));
});


test('parameter with null default - null omitted, empty string fails', function () {
	$route = new Route('<presenter>/<param>', [
		'param' => null,
	]);

	Assert::same(
		'http://example.com/homepage/12',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12]),
	);

	Assert::same(
		'http://example.com/homepage/12.1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => 12.1]),
	);

	Assert::same(
		'http://example.com/homepage/0',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => false]),
	);

	Assert::same(
		'http://example.com/homepage/1',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => true]),
	);

	Assert::same(
		'http://example.com/homepage/',
		testRouteOut($route, ['presenter' => 'homepage', 'param' => null]),
	);

	Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'param' => '']));
});
