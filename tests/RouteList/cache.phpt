<?php

declare(strict_types=1);

use Nette\Routing\Route;
use Nette\Routing\RouteList;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$list = new RouteList;
$list->add(new Route('bar', ['presenter' => 'bar']));
$list->add(new Route('<foo>', ['presenter' => 'foo']));
$list->add(new Route('<presenter>/<action>', ['presenter' => 'xxx']));
$list->add(new Route('oneway'), $list::ONE_WAY);

[$r1, $r2, $r3, $r4] = $list->getRouters();


$list->warmupCache();

Assert::with($list, function () use ($r1, $r2, $r3) {
	Assert::same('presenter', $this->cacheKey);
	Assert::same(
		[
			'*' => [$r3],
			'bar' => [$r1, $r3],
			'foo' => [$r2, $r3],
		],
		$this->ranks,
	);
});
