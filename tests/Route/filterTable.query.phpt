<?php

/**
 * Test: Nette\Routing\Route with FilterTable
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route(' ? action=<presenter>', [
	'presenter' => [
		Route::FilterTable => [
			'produkt' => 'product',
			'kategorie' => 'category',
			'zakaznik' => 'customer',
			'kosik' => 'basket',
		],
	],
]);

testRouteIn($route, '/?action=kategorie', [
	'presenter' => 'category',
	'test' => 'testvalue',
], '/?action=kategorie&test=testvalue');
