<?php

/**
 * Test: Nette\Routing\Route and constant parameter.
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('', [
	'const' => 'hello',
]);

testRouteIn($route, '/?const=foo', ['const' => 'hello', 'test' => 'testvalue'], '/?test=testvalue');

testRouteIn($route, '/?const=hello', ['const' => 'hello', 'test' => 'testvalue'], '/?test=testvalue');

Assert::same(
	'http://example.com/',
	testRouteOut($route, [])
);

Assert::null(testRouteOut($route, ['const' => 'foo']));

Assert::same(
	'http://example.com/',
	testRouteOut($route, ['const' => 'hello'])
);

Assert::same(
	'http://example.com/',
	testRouteOut($route, ['const' => null])
);
