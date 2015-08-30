<?php

/**
 * Test: Nette\Routing\Route with FilterTable
 */

declare(strict_types=1);

use Nette\Routing\Route;


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
		Route::FILTER_STRICT => true,
	],
]);

testRouteIn($route, '/kategorie/', [
	'presenter' => 'category',
	'test' => 'testvalue',
], '/kategorie?test=testvalue');

testRouteIn($route, '/other/');
