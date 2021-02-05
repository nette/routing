<?php

declare(strict_types=1);

use Nette\Routing\Route;
use Nette\Routing\RouteList;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list->add(new Route('foo', ['route' => 'foo']), RouteList::ONE_WAY);
$list->addRoute('bar', ['route' => 'bar'], RouteList::ONE_WAY);
$list->add(new Route('hello', ['route' => 'hello']));

Assert::same([RouteList::ONE_WAY, RouteList::ONE_WAY, 0], $list->getFlags());

testRouteIn($list, '/foo', ['route' => 'foo', 'test' => 'testvalue']);
testRouteIn($list, '/bar', ['route' => 'bar', 'test' => 'testvalue']);
testRouteIn($list, '/hello', ['route' => 'hello', 'test' => 'testvalue'], '/hello?test=testvalue');
testRouteIn($list, '/none');
