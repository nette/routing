<?php

/**
 * Test: Nette\Routing\SimpleRouter object parameter default value.
 */

declare(strict_types=1);

use Nette\Http;
use Nette\Routing\SimpleRouter;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$callback = function (): string {
	return 'test';
};

$router = new SimpleRouter([
	'presenter' => 'Nette:Micro',
	'callback' => $callback,
]);

$httpRequest = new Http\Request(new Http\UrlScript('https://nette.org/?foo=bar'));

$params = $router->match($httpRequest);
Assert::same([
	'foo' => 'bar',
	'presenter' => 'Nette:Micro',
	'callback' => $callback,
], $params);

$res = $router->constructUrl($params, $httpRequest->getUrl());
Assert::same('https://nette.org/?foo=bar', $res);
