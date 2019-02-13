<?php

/**
 * Test: Nette\Routing\Route with 'required' optional sequences I.
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('[!<lang [a-z]{2}>[-<sub>]/]<name>[/page-<page>]', [
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

testRouteIn($route, '/name', [
	'name' => 'name',
	'sub' => 'cz',
	'page' => null,
	'lang' => null,
	'test' => 'testvalue',
], '//name?test=testvalue');
