<?php

/**
 * Test: Nette\Routing\Route with FilterTable
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('FilterTable translates values bidirectionally', function () {
	$route = new Route('<lang>', [
		'lang' => [
			Route::FilterTable => [
				'cs' => 'czech',
				'en' => 'english',
			],
		],
	]);

	// URL value 'cs' is translated to 'czech' in params
	testRouteIn($route, '/cs', [
		'lang' => 'czech',
		'test' => 'testvalue',
	], '/cs?test=testvalue');

	// Param value 'czech' is translated back to 'cs' in URL
	Assert::same('http://example.com/cs', testRouteOut($route, ['lang' => 'czech']));

	// Non-table values pass through unchanged
	testRouteIn($route, '/de', [
		'lang' => 'de',
		'test' => 'testvalue',
	], '/de?test=testvalue');
});
