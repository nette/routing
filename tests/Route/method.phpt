<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with HTTP method restriction via Route::Method
 */

use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Routing\Route;
use Nette\Routing\RouteList;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('route without Route::Method matches any HTTP method', function () {
	$route = new Route('api/<id \d+>');
	$url = new UrlScript('http://example.com/api/12', '/');

	Assert::null($route->getMethods());
	Assert::notNull($route->match(new Request($url)));
	Assert::notNull($route->match(new Request($url, method: 'POST')));
	Assert::notNull($route->match(new Request($url, method: 'DELETE')));
});


test('route restricted to single method', function () {
	$route = new Route('api/<id \d+>', [Route::Method => 'POST']);
	$url = new UrlScript('http://example.com/api/12', '/');

	Assert::same(['POST'], $route->getMethods());
	Assert::notNull($route->match(new Request($url, method: 'POST')));
	Assert::null($route->match(new Request($url))); // GET
	Assert::null($route->match(new Request($url, method: 'DELETE')));
});


test('multiple methods as string and as array', function () {
	$url = new UrlScript('http://example.com/api/12', '/');

	$route = new Route('api/<id \d+>', [Route::Method => 'PUT|PATCH']);
	Assert::same(['PUT', 'PATCH'], $route->getMethods());
	Assert::notNull($route->match(new Request($url, method: 'PUT')));
	Assert::notNull($route->match(new Request($url, method: 'PATCH')));
	Assert::null($route->match(new Request($url)));

	$route = new Route('api/<id \d+>', [Route::Method => ['PUT', 'PATCH']]);
	Assert::same(['PUT', 'PATCH'], $route->getMethods());
	Assert::notNull($route->match(new Request($url, method: 'PATCH')));
	Assert::null($route->match(new Request($url)));
});


test('method names are case-insensitive and trimmed', function () {
	$route = new Route('api/<id \d+>', [Route::Method => 'put | patch']);
	Assert::same(['PUT', 'PATCH'], $route->getMethods());
});


test('invalid Route::Method values are rejected at construction', function () {
	Assert::exception(
		fn() => new Route('api', [Route::Method => []]),
		Nette\InvalidArgumentException::class,
		'Route::Method must specify at least one HTTP method.',
	);
	Assert::exception(
		fn() => new Route('api', [Route::Method => '']),
		Nette\InvalidArgumentException::class,
		"Invalid HTTP method '' in Route::Method.",
	);
	Assert::exception(
		fn() => new Route('api', [Route::Method => 'GET,POST']),
		Nette\InvalidArgumentException::class,
		"Invalid HTTP method 'GET,POST' in Route::Method.",
	);
	Assert::exception(
		fn() => new Route('api', [Route::Method => ['GET', 123]]),
		Nette\InvalidArgumentException::class,
		'Route::Method expects string or array of strings, int given.',
	);
	Assert::exception(
		fn() => new Route('api', [Route::Method => true]),
		Nette\InvalidArgumentException::class,
		'Route::Method expects string or array of strings, bool given.',
	);
});


test('GET implies HEAD', function () {
	$route = new Route('api/<id \d+>', [Route::Method => 'GET']);
	$url = new UrlScript('http://example.com/api/12', '/');

	Assert::same(['GET', 'HEAD'], $route->getMethods());
	Assert::notNull($route->match(new Request($url, method: 'HEAD')));
});


test('Route::Method does not leak into parameters or defaults', function () {
	$route = new Route('api/<id \d+>', ['presenter' => 'Api', Route::Method => 'GET']);
	$url = new UrlScript('http://example.com/api/12', '/');

	Assert::same(
		['id' => '12', 'presenter' => 'Api'],
		$route->match(new Request($url)),
	);
	Assert::same(['presenter' => 'Api'], $route->getDefaults());
});


test('URL generation ignores the method restriction', function () {
	$route = new Route('api/<id \d+>', [Route::Method => 'DELETE']);

	Assert::same(
		'http://example.com/api/12',
		testRouteOut($route, ['id' => 12]),
	);
});


test('RouteList dispatches by method across routes with the same mask', function () {
	$list = new RouteList;
	$list->addRoute('api/<id \d+>', ['action' => 'show', Route::Method => 'GET']);
	$list->addRoute('api/<id \d+>', ['action' => 'delete', Route::Method => 'DELETE']);

	$url = new UrlScript('http://example.com/api/12', '/');

	Assert::same('show', $list->match(new Request($url))['action']);
	Assert::same('delete', $list->match(new Request($url, method: 'DELETE'))['action']);
	Assert::null($list->match(new Request($url, method: 'POST')));
});
