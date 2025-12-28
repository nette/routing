<?php

/**
 * Test: Nette\Routing\Route with FILTER_IN & FILTER_OUT
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('FilterIn and FilterOut work on query parameters', function () {
	$route = new Route(' ? action=<presenter>', [
		'presenter' => [
			Route::FilterIn => fn($s) => strrev($s),
			Route::FilterOut => fn($s) => strtoupper(strrev($s)),
		],
	]);

	testRouteIn($route, '/?action=abc', [
		'presenter' => 'cba',
		'test' => 'testvalue',
	], '/?action=ABC&test=testvalue');
});


test('FilterIn can return non-string value', function () {
	$route = new Route('<presenter>', [
		'presenter' => [
			Route::FilterIn => fn($s) => 123, // Returns int
		],
	]);

	$url = new Nette\Http\UrlScript('http://example.com/test');
	$httpRequest = new Nette\Http\Request($url);

	$result = $route->match($httpRequest);
	Assert::notNull($result);
	Assert::same(123, $result['presenter']); // Filter can return any type
});


test('FilterOut returning null rejects URL generation', function () {
	$route = new Route('<presenter>', [
		'presenter' => [
			Route::FilterOut => fn($s) => null,
		],
	]);

	Assert::null(testRouteOut($route, ['presenter' => 'test']));
});
