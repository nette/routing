<?php

declare(strict_types=1);

use Tester\Assert;

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}


Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');


function test(string $title, Closure $function): void
{
	$function();
}


function testRouteIn(Nette\Routing\Router $route, string $relativeUrl, ?array $expectedParams = null, ?string $expectedUrl = null): void
{
	$url = new Nette\Http\UrlScript("http://example.com$relativeUrl", '/');
	$url = $url->withQuery([
		'test' => 'testvalue',
	] + $url->getQueryParameters());

	$httpRequest = new Nette\Http\Request($url);

	$params = $route->match($httpRequest);

	if ($params === null || $expectedParams === null) { // not matched
		Assert::same($expectedParams, $params);

	} else { // matched
		asort($params);
		asort($expectedParams);
		Assert::same($expectedParams, $params);

		unset($params['extra']);
		$result = $route->constructUrl($params, $url);
		$result = $result && !strncmp($result, 'http://example.com', 18)
			? substr($result, 18)
			: $result;
		Assert::same($expectedUrl, $result);
	}
}


function testRouteOut(Nette\Routing\Router $route, array $params = []): ?string
{
	$url = new Nette\Http\UrlScript('http://example.com');
	return $route->constructUrl($params, $url);
}
