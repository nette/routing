<?php

declare(strict_types=1);

use Nette\Routing\RouteList;


require __DIR__ . '/../bootstrap.php';


$sublist = (new RouteList)
	->withPath('path1')
		->addRoute('foo', ['route' => 'foo']);

$list = (new RouteList)
	->withPath('path2')
		->add($sublist);


testRouteIn($list, '/path1/path2/foo');
testRouteIn($list, '/path2/path1/foo', ['route' => 'foo', 'test' => 'testvalue'], '/path2/path1/foo?test=testvalue');
