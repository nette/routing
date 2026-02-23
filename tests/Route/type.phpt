<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route default usage.
 */

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<id=5>');
$params = ['id' => 5, 'presenter' => 'p'];

Assert::same(
	'http://example.com/?presenter=p',
	$route->constructUrl($params, new Nette\Http\UrlScript('http://example.com')),
);
