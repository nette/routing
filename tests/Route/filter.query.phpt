<?php

/**
 * Test: Nette\Routing\Route with FILTER_IN & FILTER_OUT
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route(' ? action=<presenter>', [
	'presenter' => [
		Route::FilterIn => fn($s) => strrev($s),
		Route::FilterOut => fn($s) => strtoupper(strrev($s)),
	],
]);

testRouteIn($route, '/?action=abc', [
	'presenter' => 'cba',
	'test' => 'testvalue',
], '/?action=ABC&test=testvalue');
