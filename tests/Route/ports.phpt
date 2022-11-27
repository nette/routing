<?php

/**
 * Test: Nette\Routing\Route ports
 */

declare(strict_types=1);

use Nette\Http\UrlScript;
use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('//%domain%');

$url = $route->constructUrl(
	[],
	new UrlScript('https://example.org:8000'),
);
Assert::same('https://example.org:8000', $url);

$url = $route->constructUrl(
	[],
	new UrlScript('https://localhost:8000'),
);
Assert::same('https://localhost:8000', $url);
