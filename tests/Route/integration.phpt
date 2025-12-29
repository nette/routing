<?php

/**
 * Test: Nette\Routing\Route integration tests for real-world patterns
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('blog with category, slug and optional page', function () {
	$route = new Route('blog/<category>/<slug>[/page-<page \d+>]', [
		'presenter' => 'Blog',
		'action' => 'detail',
		'page' => 1,
	]);

	// Without page
	testRouteIn($route, '/blog/tech/nette-routing-tips', [
		'presenter' => 'Blog',
		'action' => 'detail',
		'category' => 'tech',
		'slug' => 'nette-routing-tips',
		'page' => '1',
		'test' => 'testvalue',
	], '/blog/tech/nette-routing-tips?test=testvalue');

	// With page
	testRouteIn($route, '/blog/tech/nette-routing-tips/page-2', [
		'presenter' => 'Blog',
		'action' => 'detail',
		'category' => 'tech',
		'slug' => 'nette-routing-tips',
		'page' => '2',
		'test' => 'testvalue',
	], '/blog/tech/nette-routing-tips/page-2?test=testvalue');
});


test('e-commerce product with filters in query', function () {
	$route = new Route('products/<category> ? price-from=<priceFrom \d+> & price-to=<priceTo \d+> & sort=<sort>', [
		'presenter' => 'Products',
		'action' => 'list',
		'priceFrom' => null,
		'priceTo' => null,
		'sort' => 'name',
	]);

	// All filters
	testRouteIn($route, '/products/laptops?price-from=500&price-to=2000&sort=price', [
		'presenter' => 'Products',
		'action' => 'list',
		'category' => 'laptops',
		'priceFrom' => '500',
		'priceTo' => '2000',
		'sort' => 'price',
		'test' => 'testvalue',
	], '/products/laptops?price-from=500&price-to=2000&sort=price&test=testvalue');

	// Partial filters
	testRouteIn($route, '/products/phones?sort=rating', [
		'presenter' => 'Products',
		'action' => 'list',
		'category' => 'phones',
		'priceFrom' => null,
		'priceTo' => null,
		'sort' => 'rating',
		'test' => 'testvalue',
	], '/products/phones?sort=rating&test=testvalue');
});


test('localized routes with language prefix', function () {
	$route = new Route('[<lang cs|en>/]<presenter>/<action>[/<id>]', [
		'lang' => 'cs',
		'action' => 'default',
		'id' => null,
	]);

	// Czech explicit
	testRouteIn($route, '/cs/produkt/detail/123', [
		'lang' => 'cs',
		'presenter' => 'produkt',
		'action' => 'detail',
		'id' => '123',
		'test' => 'testvalue',
	], '/produkt/detail/123?test=testvalue');

	// English
	testRouteIn($route, '/en/product/detail/123', [
		'lang' => 'en',
		'presenter' => 'product',
		'action' => 'detail',
		'id' => '123',
		'test' => 'testvalue',
	], '/en/product/detail/123?test=testvalue');

	// Default language (without prefix)
	testRouteIn($route, '/about/team', [
		'lang' => 'cs',
		'presenter' => 'about',
		'action' => 'team',
		'id' => null,
		'test' => 'testvalue',
	], '/about/team?test=testvalue');
});


test('API versioning with subdomain', function () {
	$route = new Route('//api-<version v1|v2>.example.com/<endpoint>/<id>', [
		'module' => 'Api',
		'presenter' => 'Endpoint',
	]);

	// Test URL generation
	Assert::same(
		'http://api-v1.example.com/users/42',
		testRouteOut($route, [
			'module' => 'Api',
			'presenter' => 'Endpoint',
			'version' => 'v1',
			'endpoint' => 'users',
			'id' => '42',
		]),
	);

	Assert::same(
		'http://api-v2.example.com/products/123',
		testRouteOut($route, [
			'module' => 'Api',
			'presenter' => 'Endpoint',
			'version' => 'v2',
			'endpoint' => 'products',
			'id' => '123',
		]),
	);
});


test('file download with nested path', function () {
	$route = new Route('download/<year \d{4}>/<month \d{2}>/<file .+\.pdf>', [
		'presenter' => 'Download',
		'action' => 'file',
	]);

	testRouteIn($route, '/download/2024/03/invoice-2024-03-15.pdf', [
		'presenter' => 'Download',
		'action' => 'file',
		'year' => '2024',
		'month' => '03',
		'file' => 'invoice-2024-03-15.pdf',
		'test' => 'testvalue',
	], '/download/2024/03/invoice-2024-03-15.pdf?test=testvalue');

	// Invalid month - regex only checks pattern, not semantic validity
	testRouteIn($route, '/download/2024/13/invoice.pdf', [
		'presenter' => 'Download',
		'action' => 'file',
		'year' => '2024',
		'month' => '13',
		'file' => 'invoice.pdf',
		'test' => 'testvalue',
	], '/download/2024/13/invoice.pdf?test=testvalue');
});


test('user profile with username', function () {
	$route = new Route('/@<username [a-z0-9_-]+>[/<tab>]', [
		'presenter' => 'Profile',
		'action' => 'view',
		'tab' => 'overview',
	]);

	// Default tab
	testRouteIn($route, '/@john-doe', [
		'presenter' => 'Profile',
		'action' => 'view',
		'username' => 'john-doe',
		'tab' => 'overview',
		'test' => 'testvalue',
	], '/@john-doe?test=testvalue');

	// Specific tab
	testRouteIn($route, '/@jane_smith/activity', [
		'presenter' => 'Profile',
		'action' => 'view',
		'username' => 'jane_smith',
		'tab' => 'activity',
		'test' => 'testvalue',
	], '/@jane_smith/activity?test=testvalue');
});


test('admin module with nested resources', function () {
	$route = new Route('admin/<module>/<presenter>/<action=default>[/<id \d+>]', [
		'module' => 'Admin',
	]);

	testRouteIn($route, '/admin/catalog/products/edit/42', [
		'module' => 'catalog',
		'presenter' => 'products',
		'action' => 'edit',
		'id' => '42',
		'test' => 'testvalue',
	], '/admin/catalog/products/edit/42?test=testvalue');

	testRouteIn($route, '/admin/users/permissions', [
		'module' => 'users',
		'presenter' => 'permissions',
		'action' => 'default',
		'id' => null,
		'test' => 'testvalue',
	], '/admin/users/permissions/?test=testvalue');
});


test('combined filters and optional sequences', function () {
	$route = new Route('<presenter>/<action>[/<id>]', [
		'presenter' => [
			Route::FilterTable => [
				'home' => 'Homepage',
				'about' => 'About',
			],
		],
		'action' => 'default',
		'id' => null,
	]);

	// Table filter applies
	testRouteIn($route, '/home', [
		'presenter' => 'Homepage',
		'action' => 'default',
		'id' => null,
		'test' => 'testvalue',
	], '/home/?test=testvalue');

	// Non-table value passes through
	testRouteIn($route, '/blog/archive/2024', [
		'presenter' => 'blog',
		'action' => 'archive',
		'id' => '2024',
		'test' => 'testvalue',
	], '/blog/archive/2024?test=testvalue');
});


test('search with pagination and sorting', function () {
	$route = new Route('search/<query> ? page=<page \d+> & sort=<sort>', [
		'presenter' => 'Search',
		'action' => 'results',
		'page' => '1',
		'sort' => 'relevance',
	]);

	testRouteIn($route, '/search/nette%20framework?page=2&sort=date', [
		'presenter' => 'Search',
		'action' => 'results',
		'query' => 'nette framework',
		'page' => '2',
		'sort' => 'date',
		'test' => 'testvalue',
	], '/search/nette%20framework?page=2&sort=date&test=testvalue');
});


test('REST API with HTTP method simulation', function () {
	$route = new Route('api/<resource>/<id \d+>', [
		'module' => 'Api',
		'presenter' => 'Rest',
	]);

	testRouteIn($route, '/api/users/123', [
		'module' => 'Api',
		'presenter' => 'Rest',
		'resource' => 'users',
		'id' => '123',
		'test' => 'testvalue',
	], '/api/users/123?test=testvalue');
});
