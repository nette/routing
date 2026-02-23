<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with FilterTable
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route(' ? action=<presenter>', [
	'presenter' => [
		Route::FilterTable => [
			'produkt' => 'Product',
			'kategorie' => 'Category',
			'zakaznik' => 'Customer',
			'kosik' => 'Basket',
		],
		Route::FilterIn => 'ucwords',
		Route::FilterOut => 'lcfirst',
	],
]);

testRouteIn($route, '/?action=kategorie', [
	'presenter' => 'Category',
	'test' => 'testvalue',
], '/?action=kategorie&test=testvalue');
