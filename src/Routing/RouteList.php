<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Routing;

use Nette;


/**
 * The router broker.
 */
class RouteList extends Nette\Utils\ArrayList implements IRouter
{
	/** @var array */
	private $cachedRoutes;


	public function __construct()
	{
	}


	/**
	 * Maps HTTP request to an array.
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		foreach ($this as $route) {
			$params = $route->match($httpRequest);
			if ($params !== null) {
				return $params;
			}
		}
		return null;
	}


	/**
	 * Constructs absolute URL from array.
	 */
	public function constructUrl(array $params, Nette\Http\Url $refUrl): ?string
	{
		if ($this->cachedRoutes === null) {
			$this->warmupCache();
		}

		foreach ($this->cachedRoutes as $route) {
			$url = $route->constructUrl($params, $refUrl);
			if ($url !== null) {
				return $url;
			}
		}

		return null;
	}


	public function warmupCache(): void
	{
		$routes = [];
		foreach ($this as $route) {
			$routes[] = $route;
		}
		$this->cachedRoutes = $routes;
	}


	/**
	 * Adds the router.
	 * @param  mixed  $index
	 * @param  IRouter  $route
	 */
	public function offsetSet($index, $route): void
	{
		if (!$route instanceof IRouter) {
			throw new Nette\InvalidArgumentException('Argument must be IRouter descendant.');
		}
		parent::offsetSet($index, $route);
	}
}
