<?php declare(strict_types=1);

use Nette\Http;
use Nette\Routing\SimpleRouter;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$object = new stdClass;
$router = new SimpleRouter([
	'object' => $object,
	'array' => [1, 2],
]);

$httpRequest = new Http\Request(new Http\UrlScript('https://nette.org/?foo=bar'));

$params = $router->match($httpRequest);
Assert::same([
	'foo' => 'bar',
	'object' => $object,
	'array' => [1, 2],
], $params);

$res = $router->constructUrl([
	'object' => new stdClass,
	'array' => [1, 2],
], $httpRequest->getUrl());
Assert::same('https://nette.org/', $res);

$res = $router->constructUrl([
	'object' => (object) ['a' => 1, 'b' => [(object) ['c' => 2]]],
], $httpRequest->getUrl());
Assert::same('https://nette.org/?object%5Ba%5D=1&object%5Bb%5D%5B0%5D%5Bc%5D=2', $res);
