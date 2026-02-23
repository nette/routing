<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with Secured
 */

use Nette\Http\UrlScript;
use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<param>');

$url = $route->constructUrl(
	['param' => 'any'],
	new UrlScript('https://example.org'),
);
Assert::same('https://example.org/any', $url);
