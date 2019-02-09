<?php

/**
 * Test: Nette\Routing\Route default usage.
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>/<action=default>/<id= \d{1,3}>');

Assert::same('http://example.com/homepage/', testRouteOut($route, ['presenter' => 'homepage']));

Assert::same('http://example.com/homepage/', testRouteOut($route, ['presenter' => 'homepage', 'action' => 'default']));

Assert::null(testRouteOut($route, ['presenter' => 'homepage', 'id' => 'word']));

testRouteIn($route, '/presenter/action/12/any');

testRouteIn($route, '/presenter/action/12/', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '12',
	'test' => 'testvalue',
], '/presenter/action/12?test=testvalue');

testRouteIn($route, '/presenter/action/12', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '12',
	'test' => 'testvalue',
], '/presenter/action/12?test=testvalue');

testRouteIn($route, '/presenter/action/1234');

testRouteIn($route, '/presenter/action/', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '',
	'test' => 'testvalue',
], '/presenter/action/?test=testvalue');

testRouteIn($route, '/presenter/action', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '',
	'test' => 'testvalue',
], '/presenter/action/?test=testvalue');

testRouteIn($route, '/presenter/', [
	'presenter' => 'presenter',
	'id' => '',
	'action' => 'default',
	'test' => 'testvalue',
], '/presenter/?test=testvalue');

testRouteIn($route, '/presenter', [
	'presenter' => 'presenter',
	'id' => '',
	'action' => 'default',
	'test' => 'testvalue',
], '/presenter/?test=testvalue');

testRouteIn($route, '/');
