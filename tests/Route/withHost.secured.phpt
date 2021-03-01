<?php

/**
 * Test: Nette\Routing\Route with WithHost
 */

declare(strict_types=1);

use Nette\Http\UrlScript;
use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('//example.org/test');

$url = $route->constructUrl(
	[],
	new UrlScript('https://example.org'),
);
Assert::same('https://example.org/test', $url);

$url = $route->constructUrl(
	[],
	new UrlScript('https://example.com'),
);
Assert::same('https://example.org/test', $url);



$route = new Route('https://example.org/test');

$url = $route->constructUrl(
	[],
	new UrlScript('https://example.org'),
);
Assert::same('https://example.org/test', $url);

$url = $route->constructUrl(
	[],
	new UrlScript('https://example.com'),
);
Assert::same('https://example.org/test', $url);
