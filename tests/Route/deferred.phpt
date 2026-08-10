<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route and deferred parameter values in constructUrl().
 */

use Nette\Routing\{Route, SimpleRouter, TemplateRouter};
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('constructUrl() refuses a deferred path parameter', function () {
	$route = new Route('article/<id \d+>', ['controller' => 'Article']);
	Assert::exception(
		fn() => testRouteOut($route, ['controller' => 'Article', 'id' => TemplateRouter::Deferred]),
		Nette\InvalidArgumentException::class,
		'Parameter $id is deferred, use constructTemplate() to build a link template.',
	);
});


test('constructUrl() refuses a deferred parameter outside the mask', function () {
	$route = new Route('products', ['controller' => 'Product']);
	Assert::exception(
		fn() => testRouteOut($route, ['controller' => 'Product', 'sort' => TemplateRouter::Deferred]),
		Nette\InvalidArgumentException::class,
		'Parameter $sort is deferred, use constructTemplate() to build a link template.',
	);
});


test('SimpleRouter::constructUrl() refuses a deferred parameter', function () {
	$router = new SimpleRouter(['controller' => 'Home']);
	Assert::exception(
		fn() => $router->constructUrl(
			['controller' => 'Product', 'id' => TemplateRouter::Deferred],
			new Nette\Http\UrlScript('http://example.com/'),
		),
		Nette\InvalidArgumentException::class,
		'Parameter $id is deferred, use constructTemplate() to build a link template.',
	);
});
