<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with UrlEncoding
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<param .*>');

testRouteIn($route, '/a%3A%25%2Fb', [
	'param' => 'a:%/b',
	'test' => 'testvalue',
], '/a:%25/b?test=testvalue');


$route = new Route('<param .*>', [
	'param' => [
		Route::FilterOut => 'rawurlencode',
	],
]);

testRouteIn($route, '/a%3A%25%2Fb', [
	'param' => 'a:%/b',
	'test' => 'testvalue',
], '/a%3A%25%2Fb?test=testvalue');
