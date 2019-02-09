<?php

/**
 * Test: Nette\Routing\Route with ArrayParams
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route(' ? arr=<arr>', [
	'arr' => '',
]);

testRouteIn($route, '/?arr[1]=1&arr[2]=2', [
	'arr' => [
		1 => '1',
		2 => '2',
	],
	'test' => 'testvalue',
], '/?test=testvalue&arr%5B1%5D=1&arr%5B2%5D=2');
