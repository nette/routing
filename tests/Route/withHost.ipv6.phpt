<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\Route with %host% wildcard and IP address hosts
 */

use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('IPv4 host is not split into domain parts', function () {
	$route = new Route('//%host%/<presenter>');
	$url = new UrlScript('http://192.168.0.1/homepage', '/');

	$params = $route->match(new Request($url));
	Assert::same('homepage', $params['presenter']);
	Assert::same('http://192.168.0.1/homepage', $route->constructUrl($params, $url));
});


test('IPv6 host is not split into domain parts', function () {
	$route = new Route('//%host%/<presenter>');
	$url = new UrlScript('http://[2001:db8::1]/homepage', '/');

	$params = $route->match(new Request($url));
	Assert::same('homepage', $params['presenter']);
	Assert::same('http://[2001:db8::1]/homepage', $route->constructUrl($params, $url));
});
