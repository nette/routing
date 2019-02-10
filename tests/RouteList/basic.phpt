<?php

declare(strict_types=1);

use Nette\Routing\Route;
use Nette\Routing\RouteList;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list->add(new Route('bar', ['route' => 'bar']));
$list->add(new Route('<foo>', ['route' => 'foo']));


testRouteIn($list, '/bar', ['route' => 'bar', 'test' => 'testvalue'], '/bar?test=testvalue');
testRouteIn($list, '/none', ['route' => 'foo', 'foo' => 'none', 'test' => 'testvalue'], '/none?test=testvalue');
