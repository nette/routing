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
class RouteList implements Router
{
	use Nette\SmartObject;

	/** @var array of [Router, flags] */
	private $list = [];

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
		foreach ($this->list as [$router]) {
			$params = $router->match($httpRequest);
			if ($params !== null) {
				return $params;
			}
		}
		return null;
	}


	/**
	 * Constructs absolute URL from array.
	 */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
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
		foreach ($this->list as [$router, $flags]) {
			if ($flags & self::ONE_WAY) {
				continue;
			}
			$routes[] = $router;
		}
		$this->cachedRoutes = $routes;
	}


	/**
	 * Adds a router.
	 * @return static
	 */
	public function add(Router $router, int $flags = 0)
	{
		$this->list[] = [$router, $flags];
		return $this;
	}


	/**
	 * Prepends a router.
	 */
	public function prepend(Router $router, int $flags = 0): void
	{
		array_splice($this->list, 0, 0, [[$router, $flags]]);
	}


	/** @internal */
	protected function modify(int $index, ?Router $router): void
	{
		if (!isset($this->list[$index])) {
			throw new Nette\OutOfRangeException('Offset invalid or out of range');
		} elseif ($router) {
			$this->list[$index] = [$router, 0];
		} else {
			array_splice($this->list, $index, 1);
		}
	}


	/**
	 * @return Router[]
	 */
	public function getRouters(): array
	{
		return array_column($this->list, 0);
	}
}
