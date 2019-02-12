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

	/** @var Router[][]|null */
	private $ranks;

	/** @var string */
	private $cacheKey;


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
		if ($this->ranks === null) {
			$this->warmupCache();
		}

		$key = $params[$this->cacheKey] ?? null;
		if (!is_scalar($key) || !isset($this->ranks[$key])) {
			$key = '*';
		}

		foreach ($this->ranks[$key] as $router) {
			$url = $router->constructUrl($params, $refUrl);
			if ($url !== null) {
				return $url;
			}
		}

		return null;
	}


	public function warmupCache(): void
	{
		// find best key
		$candidates = [];
		$routers = [];
		foreach ($this->list as [$router, $flags]) {
			if ($flags & self::ONE_WAY) {
				continue;
			} elseif ($router instanceof self) {
				$router->warmupCache();
			}
			$params = $router instanceof Route
				? $router->getConstantParameters()
				: [];

			foreach (array_filter($params, 'is_scalar') as $name => $value) {
				$candidates[$name][$value] = true;
			}
			$routers[] = [$router, $params];
		}

		$this->cacheKey = $count = null;
		foreach ($candidates as $name => $items) {
			if (count($items) > $count) {
				$count = count($items);
				$this->cacheKey = $name;
			}
		}

		// classify routers
		$ranks = ['*' => []];

		foreach ($routers as [$router, $params]) {
			$value = $params[$this->cacheKey] ?? null;
			$values = $value === null
				? array_keys($ranks)
				: [is_scalar($value) ? $value : '*'];

			foreach ($values as $value) {
				if (!isset($ranks[$value])) {
					$ranks[$value] = $ranks['*'];
				}
				$ranks[$value][] = $router;
			}
		}

		$this->ranks = $ranks;
	}


	/**
	 * Adds a router.
	 * @return static
	 */
	public function add(Router $router, int $flags = 0)
	{
		$this->list[] = [$router, $flags];
		$this->ranks = null;
		return $this;
	}


	/**
	 * Prepends a router.
	 */
	public function prepend(Router $router, int $flags = 0): void
	{
		array_splice($this->list, 0, 0, [[$router, $flags]]);
		$this->ranks = null;
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
		$this->ranks = null;
	}


	/**
	 * @param  string  $mask  e.g. '<presenter>/<action>/<id \d{1,3}>'
	 * @param  array  $metadata  default values or metadata
	 * @return static
	 */
	public function addRoute(string $mask, $metadata = [], int $flags = 0)
	{
		$this->add(new Route($mask, $metadata), $flags);
		return $this;
	}


	/**
	 * @return Router[]
	 */
	public function getRouters(): array
	{
		return array_column($this->list, 0);
	}
}
