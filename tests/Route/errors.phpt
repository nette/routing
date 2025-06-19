<?php

/**
 * Test: Nette\Routing\Route errors.
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::exception(
	fn() => new Route('[a'),
	Nette\InvalidArgumentException::class,
	"Unexpected '[' in mask '[a'.",
);

Assert::exception(
	fn() => new Route('a]'),
	Nette\InvalidArgumentException::class,
	"Missing '[' in mask 'a]'.",
);

Assert::exception(
	fn() => new Route('<presenter>/<action'),
	Nette\InvalidArgumentException::class,
	"Unexpected '/<action' in mask '<presenter>/<action'.",
);

Assert::exception(
	fn() => new Route('<presenter>/action>'),
	Nette\InvalidArgumentException::class,
	"Unexpected '/action>' in mask '<presenter>/action>'.",
);
