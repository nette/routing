<?php

/**
 * Test: Nette\Routing\Route with FooParameter
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


test('basic foo parameter with single extension', function () {
	$route = new Route('index<?.xml>/', [
		'presenter' => 'defaultPresenter',
	]);

	testRouteIn($route, '/index.');

	testRouteIn($route, '/index.xml', [
		'presenter' => 'defaultPresenter',
		'test' => 'testvalue',
	], '/index.xml/?test=testvalue');

	testRouteIn($route, '/index.php');

	testRouteIn($route, '/index');
});


test('foo parameter with multiple extensions - both required', function () {
	$route = new Route('file<?.xml><?.gz>', [
		'presenter' => 'File',
	]);

	testRouteIn($route, '/file.xml.gz', [
		'presenter' => 'File',
		'test' => 'testvalue',
	], '/file.xml.gz?test=testvalue');

	// Only .xml doesn't match - both patterns required
	testRouteIn($route, '/file.xml');

	// No extension doesn't match
	testRouteIn($route, '/file');
});


test('foo parameter combined with named parameters', function () {
	$route = new Route('<name>[<?.html>]', [
		'presenter' => 'Page',
	]);

	testRouteIn($route, '/about.html', [
		'name' => 'about',
		'presenter' => 'Page',
		'test' => 'testvalue',
	], '/about?test=testvalue'); // Foo parameter not generated in URL

	testRouteIn($route, '/contact', [
		'name' => 'contact',
		'presenter' => 'Page',
		'test' => 'testvalue',
	], '/contact?test=testvalue');
});
