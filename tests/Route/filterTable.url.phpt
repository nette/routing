<?php

/**
 * Test: Nette\Application\Routers\Route with FilterTable
 */

declare(strict_types=1);

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('<presenter>', [
	'presenter' => [
		Route::FILTER_TABLE => [
			'produkt' => 'product',
			'kategorie' => 'category',
			'zakaznik' => 'customer',
			'kosik' => 'basket',
		],
	],
]);

testRouteIn($route, '/kategorie/', [
	'presenter' => 'category',
	'test' => 'testvalue',
], '/kategorie?test=testvalue');

testRouteIn($route, '/other/', [
	'presenter' => 'other',
	'test' => 'testvalue',
], '/other?test=testvalue');
