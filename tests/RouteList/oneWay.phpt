<?php

declare(strict_types=1);

use Nette\Routing\Route;
use Nette\Routing\RouteList;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list->add(new Route('foo', ['route' => 'foo']), oneWay: true);
$list->addRoute('bar', ['route' => 'bar'], oneWay: true);
$list->add(new Route('hello', ['route' => 'hello']));

Assert::same([['oneWay' => true], ['oneWay' => true], ['oneWay' => false]], $list->getFlags());

testRouteIn($list, '/foo', ['route' => 'foo', 'test' => 'testvalue']);
testRouteIn($list, '/bar', ['route' => 'bar', 'test' => 'testvalue']);
testRouteIn($list, '/hello', ['route' => 'hello', 'test' => 'testvalue'], '/hello?test=testvalue');
testRouteIn($list, '/none');
