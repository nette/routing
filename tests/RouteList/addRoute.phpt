<?php

declare(strict_types=1);

use Nette\Routing\RouteList;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list->addRoute('foo', ['route' => 'foo'], oneWay: true);
$list->addRoute('bar', ['route' => 'bar'], oneWay: true);
$list->addRoute('hello', ['route' => 'hello']);


testRouteIn($list, '/foo', ['route' => 'foo', 'test' => 'testvalue']);
testRouteIn($list, '/bar', ['route' => 'bar', 'test' => 'testvalue']);
testRouteIn($list, '/hello', ['route' => 'hello', 'test' => 'testvalue'], '/hello?test=testvalue');
testRouteIn($list, '/none');
