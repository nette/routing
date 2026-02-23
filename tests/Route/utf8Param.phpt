<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route UTF-8 parameter.
 */

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<param č>');

testRouteIn($route, '/č', [
	'param' => 'č',
	'test' => 'testvalue',
], '/%C4%8D?test=testvalue');

testRouteIn($route, '/%C4%8D', [
	'param' => 'č',
	'test' => 'testvalue',
], '/%C4%8D?test=testvalue');

testRouteIn($route, '/');
