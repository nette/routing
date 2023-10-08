<?php

declare(strict_types=1);

use Nette\Routing\Route;
use Nette\Routing\RouteList;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list->add($r1 = new Route('bar', ['route' => 'bar']));
$list->add($r2 = new Route('<foo>', ['route' => 'foo']));

Assert::same(
	[$r1, $r2],
	$list->getRouters()
);


$list->prepend($r3 = new Route('<foo>', ['route' => 'foo']));

Assert::same(
	[$r3, $r1, $r2],
	$list->getRouters()
);
