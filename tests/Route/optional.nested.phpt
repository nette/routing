<?php

/**
 * Test: Nette\Routing\Route with nested optional sequences.
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('[<lang [a-z]{2}>[-<sub>]/]<name>[/page-<page>]', [
	'sub' => 'cz',
]);

testRouteIn($route, '/cs-cz/name', [
	'lang' => 'cs',
	'sub' => 'cz',
	'name' => 'name',
	'page' => null,
	'test' => 'testvalue',
], '/cs/name?test=testvalue');

testRouteIn($route, '/cs-xx/name', [
	'lang' => 'cs',
	'sub' => 'xx',
	'name' => 'name',
	'page' => null,
	'test' => 'testvalue',
], '/cs-xx/name?test=testvalue');

testRouteIn($route, '/cs/name', [
	'lang' => 'cs',
	'name' => 'name',
	'sub' => 'cz',
	'page' => null,
	'test' => 'testvalue',
], '/cs/name?test=testvalue');

testRouteIn($route, '/name', [
	'name' => 'name',
	'sub' => 'cz',
	'page' => null,
	'lang' => null,
	'test' => 'testvalue',
], '/name?test=testvalue');

testRouteIn($route, '/name/page-0', [
	'name' => 'name',
	'page' => '0',
	'sub' => 'cz',
	'lang' => null,
	'test' => 'testvalue',
], '/name/page-0?test=testvalue');

testRouteIn($route, '/name/page-');

testRouteIn($route, '/');
