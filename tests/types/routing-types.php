<?php

/**
 * PHPStan type tests for Routing.
 * Run: vendor/bin/phpstan analyse tests/types
 */

declare(strict_types=1);

use Nette\Http\IRequest;
use Nette\Http\UrlScript;
use Nette\Routing\RouteList;
use Nette\Routing\Router;
use function PHPStan\Testing\assertType;


function testRouterMatch(Router $router, IRequest $request): void
{
	$result = $router->match($request);
	assertType('array<string, mixed>|null', $result);
}


function testRouterConstructUrl(Router $router, UrlScript $url): void
{
	$result = $router->constructUrl(['action' => 'default'], $url);
	assertType('string|null', $result);
}


function testRouteListGetRouters(RouteList $list): void
{
	assertType('list<Nette\Routing\Router>', $list->getRouters());
}


function testRouteListGetFlags(RouteList $list): void
{
	assertType('list<int>', $list->getFlags());
}


function testRouteListAdd(RouteList $list, Router $router): void
{
	assertType('Nette\Routing\RouteList', $list->add($router));
}


function testRouteListAddRoute(RouteList $list): void
{
	assertType('Nette\Routing\RouteList', $list->addRoute('<presenter>/<action>'));
}


function testRouteListWithDomain(RouteList $list): void
{
	assertType('Nette\Routing\RouteList', $list->withDomain('example.com'));
}


function testRouteListEnd(RouteList $list): void
{
	assertType('Nette\Routing\RouteList|null', $list->end());
}
