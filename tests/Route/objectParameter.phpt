<?php

/**
 * Test: Nette\Routing\Route object parameter default value.
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('', function () {
	$object = new stdClass;

	$route = new Route('', [
		'param' => $object,
	]);

	Assert::same(
		'http://example.com/',
		testRouteOut($route, ['param' => $object]),
	);

	Assert::same(
		'http://example.com/',
		testRouteOut($route, ['param' => new stdClass]),
	);
});
