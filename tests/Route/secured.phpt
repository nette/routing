<?php

/**
 * Test: Nette\Routing\Route with Secured
 */

declare(strict_types=1);

use Nette\Http\Url;
use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('<param>');

$url = $route->constructUrl(
	['param' => 'any'],
	new Url('https://example.org')
);
Assert::same('https://example.org/any', $url);
