<?php

/**
 * Test: Nette\Routing\Route with FilterTable
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>', [
	'presenter' => [
		Route::FilterTable => [
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
