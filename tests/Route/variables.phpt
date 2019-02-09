<?php

/**
 * Test: Nette\Routing\Route with %variables%
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


testRouteIn(new Route('//<?%domain%>/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


testRouteIn(new Route('//example.<?%tld%>/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


testRouteIn(new Route('//example.com/<?%basePath%>/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


testRouteIn(new Route('//%domain%/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


testRouteIn(new Route('//%sld%.com/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


testRouteIn(new Route('//%sld%.%tld%/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


testRouteIn(new Route('//%host%/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


// alternative
testRouteIn(new Route('//example.%tld%/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


testRouteIn(new Route('//example.com/%basePath%/<path>'), '/abc', [
	'path' => 'abc',
	'test' => 'testvalue',
], '/abc?test=testvalue');


// IP
$url = new Nette\Http\UrlScript('http://192.168.100.100/');
$httpRequest = new Nette\Http\Request($url);
$route = new Route('//%domain%/');
Assert::same('http://192.168.100.100/', $route->constructUrl($route->match($httpRequest), $url));

$route = new Route('//%tld%/');
Assert::same('http://192.168.100.100/', $route->constructUrl($route->match($httpRequest), $url));


$url = new Nette\Http\UrlScript('http://[2001:db8::1428:57ab]/');
$httpRequest = new Nette\Http\Request($url);
$route = new Route('//%domain%/');
Assert::same('http://[2001:db8::1428:57ab]/', $route->constructUrl($route->match($httpRequest), $url));

$route = new Route('//%tld%/');
Assert::same('http://[2001:db8::1428:57ab]/', $route->constructUrl($route->match($httpRequest), $url));


// special
$url = new Nette\Http\UrlScript('http://localhost/');
$httpRequest = new Nette\Http\Request($url);
$route = new Route('//%domain%/');
Assert::same('http://localhost/', $route->constructUrl($route->match($httpRequest), $url));

$route = new Route('//%tld%/');
Assert::same('http://localhost/', $route->constructUrl($route->match($httpRequest), $url));


// host
$url = new Nette\Http\UrlScript('http://www.example.com/');
$httpRequest = new Nette\Http\Request($url);
$route = new Route('//%host%/');
Assert::same('http://www.example.com/', $route->constructUrl($route->match($httpRequest), $url));
