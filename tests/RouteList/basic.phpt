<?php

/**
 * Test: Nette\Routing\RouteList default usage.
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Nette\Routing\RouteList;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list[] = new Route('<presenter>/<action=default>/<id= \d{1,3}>');


Assert::same('http://example.com/homepage/', testRouteOut($list, ['presenter' => 'homepage']));

testRouteIn($list, '/presenter/action/12/any');

testRouteIn($list, '/presenter/action/12/', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '12',
	'test' => 'testvalue',
], '/presenter/action/12?test=testvalue');

testRouteIn($list, '/presenter/action/12/any');

testRouteIn($list, '/presenter/action/12/', [
	'presenter' => 'presenter',
	'action' => 'action',
	'id' => '12',
	'test' => 'testvalue',
], '/presenter/action/12?test=testvalue');

testRouteIn($list, '/');
