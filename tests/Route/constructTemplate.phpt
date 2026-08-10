<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route::constructTemplate() and LinkTemplate with value handlers.
 */

use Nette\Routing\{Route, RouteList, SimpleRouter, TemplateRouter};
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$refUrl = new Nette\Http\UrlScript('http://example.com/');


test('path parameter with a pattern: identity variable, expand matches constructUrl byte for byte', function () use ($refUrl) {
	$route = new Route('article/<id \d+>', ['controller' => 'Article']);
	$template = $route->constructTemplate(
		['controller' => 'Article', 'id' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/article/{id}', $template->toTemplateString());
	Assert::same(
		$route->constructUrl(['controller' => 'Article', 'id' => '12'], $refUrl),
		$template->expand(['id' => '12']),
	);
});


test('filter table variable: expand takes the application value and maps it like the route', function () use ($refUrl) {
	$route = new Route('<lang>/products', [
		'controller' => 'Product',
		'lang' => [Route::FilterTable => ['cz' => 'cs'], Route::FilterStrict => true],
	]);
	$template = $route->constructTemplate(
		['controller' => 'Product', 'lang' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/{lang}/products', $template->toTemplateString());
	Assert::same(
		$route->constructUrl(['controller' => 'Product', 'lang' => 'cs'], $refUrl),
		$template->expand(['lang' => 'cs']),
	);
	Assert::exception(
		fn() => $template->expand(['lang' => 'en']),
		Nette\InvalidArgumentException::class,
		"Value 'en' of parameter \$lang not found in filter table.",
	);
});


test('custom out filter variable: expand applies the filter and inserts its output raw', function () use ($refUrl) {
	$route = new Route('report/<date>', [
		'controller' => 'Report',
		'date' => [Route::FilterOut => fn(string $s) => date('Y-m-d', (int) $s)],
	]);
	$template = $route->constructTemplate(
		['controller' => 'Report', 'date' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same(
		$route->constructUrl(['controller' => 'Report', 'date' => '1700000000'], $refUrl),
		$template->expand(['date' => '1700000000']),
	);
});


test('renamed query parameter keeps its URL name and applies the filter', function () use ($refUrl) {
	$route = new Route('products ? item=<id>', [
		'controller' => 'Product',
		'id' => [Route::FilterOut => fn(string $s) => 'i' . $s],
	]);
	$template = $route->constructTemplate(
		['controller' => 'Product', 'id' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/products?item={id}', $template->toTemplateString());
	Assert::same(
		$route->constructUrl(['controller' => 'Product', 'id' => '7'], $refUrl),
		$template->expand(['id' => '7']),
	);
});


test('a parameter outside the mask becomes a query variable', function () use ($refUrl) {
	$route = new Route('products', ['controller' => 'Product']);
	$template = $route->constructTemplate(
		['controller' => 'Product', 'sort' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/products?sort={sort}', $template->toTemplateString());
	Assert::same('http://example.com/products?sort=name', $template->expand(['sort' => 'name']));
	Assert::same('http://example.com/products', $template->expand([]));
});


test('an array value expands byte-identically to constructUrl', function () use ($refUrl) {
	$route = new Route('products', ['controller' => 'Product']);
	$template = $route->constructTemplate(
		['controller' => 'Product', 'tags' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same(
		$route->constructUrl(['controller' => 'Product', 'tags' => ['a', 'b c']], $refUrl),
		$template->expand(['tags' => ['a', 'b c']]),
	);
});


test('a parameter with a default value is not elided when deferred', function () use ($refUrl) {
	$route = new Route('list/<page=1>', ['controller' => 'List']);
	$template = $route->constructTemplate(
		['controller' => 'List', 'page' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/list/{page}', $template->toTemplateString());
});


test('multi-segment .+ parameter: expand keeps slashes intact', function () use ($refUrl) {
	$route = new Route('<path .+>', ['controller' => 'File']);
	$template = $route->constructTemplate(
		['controller' => 'File', 'path' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/{path}', $template->toTemplateString());
	Assert::same('http://example.com/a/b%20c', $template->expand(['path' => 'a/b c']));
});


test('constant parameter cannot be deferred, the route does not apply', function () use ($refUrl) {
	$route = new Route('tos', ['controller' => 'Article', 'id' => '123']);
	Assert::null($route->constructTemplate(
		['controller' => 'Article', 'id' => TemplateRouter::Deferred],
		$refUrl,
	));
});


test('a deferred parameter swallowed by a global filter is reported', function () use ($refUrl) {
	$route = new Route('products', [
		'controller' => 'Product',
		'' => [Route::FilterOut => function (array $params) {
			unset($params['sort']);
			return $params;
		}],
	]);
	Assert::exception(
		fn() => $route->constructTemplate(['controller' => 'Product', 'sort' => TemplateRouter::Deferred], $refUrl),
		Nette\InvalidArgumentException::class,
		'Parameter $sort cannot be deferred, the route does not pass its value through to the URL.',
	);
});


test('SimpleRouter builds a query-variable template', function () use ($refUrl) {
	$router = new SimpleRouter(['controller' => 'Home']);
	$template = $router->constructTemplate(
		['controller' => 'Product', 'id' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/?controller=Product&id={id}', $template->toTemplateString());
	Assert::same('http://example.com/?controller=Product&id=5', $template->expand(['id' => 5]));
});


test('RouteList delegates to the first owning router', function () use ($refUrl) {
	$list = new RouteList;
	$list->addRoute('product/<id \d+>', ['controller' => 'Product']);
	$list->add(new SimpleRouter(['controller' => 'Home']));
	$template = $list->constructTemplate(
		['controller' => 'Product', 'id' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/product/{id}', $template->toTemplateString());
	Assert::same('http://example.com/product/5', $template->expand(['id' => '5']));
});


test('a non-owning router without the capability is passed over', function () use ($refUrl) {
	$list = new RouteList;
	$list->add(new class implements Nette\Routing\Router {
		public function match(Nette\Http\IRequest $httpRequest): ?array
		{
			return [];
		}


		public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
		{
			return isset($params['slug']) ? 'http://example.com/custom' : null;
		}
	});
	$list->addRoute('product/<id \d+>', ['controller' => 'Product']);
	$template = $list->constructTemplate(
		['controller' => 'Product', 'id' => TemplateRouter::Deferred],
		$refUrl,
	);
	Assert::same('http://example.com/product/{id}', $template->toTemplateString());
});


test('an owning router without the capability fails loudly instead of losing ownership', function () use ($refUrl) {
	$list = new RouteList;
	$list->add(new class implements Nette\Routing\Router {
		public function match(Nette\Http\IRequest $httpRequest): ?array
		{
			return [];
		}


		public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
		{
			return 'http://example.com/custom';
		}
	});
	$list->addRoute('product/<id \d+>', ['controller' => 'Product']);
	Assert::exception(
		fn() => $list->constructTemplate(['controller' => 'Product', 'id' => TemplateRouter::Deferred], $refUrl),
		Nette\InvalidArgumentException::class,
		'Cannot build a link template: router %a% does not implement Nette\Routing\TemplateRouter.',
	);
});
