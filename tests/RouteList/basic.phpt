<?php

declare(strict_types=1);

use Nette\Routing\Route;
use Nette\Routing\RouteList;


require __DIR__ . '/../bootstrap.php';


test('RouteList with many routes - first match wins', function () {
	$list = new RouteList;
	$list->add(new Route('product/<id \d+>', ['presenter' => 'Product', 'action' => 'detail']));
	$list->add(new Route('product/<slug>', ['presenter' => 'Product', 'action' => 'view']));
	$list->add(new Route('<presenter>/<action>', []));

	// First route matches numeric id
	testRouteIn($list, '/product/123', [
		'presenter' => 'Product',
		'action' => 'detail',
		'id' => '123',
		'test' => 'testvalue',
	], '/product/123?test=testvalue');

	// Second route matches slug
	testRouteIn($list, '/product/nette-framework', [
		'presenter' => 'Product',
		'action' => 'view',
		'slug' => 'nette-framework',
		'test' => 'testvalue',
	], '/product/nette-framework?test=testvalue');
});


test('RouteList returns null when no route matches', function () {
	$list = new RouteList;
	$list->add(new Route('api/<endpoint>', ['module' => 'Api']));

	testRouteIn($list, '/invalid/path');
});
