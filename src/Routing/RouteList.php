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
	protected ?self $parent;

	/** @var array<array{Router, int}> */
	private array $list = [];

	/** @var Router[][]|null */
	private ?array $ranks = null;
	private ?string $cacheKey;
	private ?string $domain = null;
	private ?string $path = null;
	private ?\SplObjectStorage $refUrlCache;


	public function __construct()
	{
	}


	/**
	 * Maps HTTP request to an array.
	 * @final
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		if ($httpRequest = $this->prepareRequest($httpRequest)) {
			foreach ($this->list as [$router]) {
				if (
					($params = $router->match($httpRequest)) !== null
					&& ($params = $this->completeParameters($params)) !== null
				) {
					return $params;
				}
			}
		}
		return null;
	}


	protected function prepareRequest(Nette\Http\IRequest $httpRequest): ?Nette\Http\IRequest
	{
		if ($this->domain) {
			$host = $httpRequest->getUrl()->getHost();
			if ($host !== $this->expandDomain($host)) {
				return null;
			}
		}

		if ($this->path) {
			$url = $httpRequest->getUrl();
			$relativePath = $url->getRelativePath();
			if (strncmp($relativePath, $this->path, strlen($this->path)) === 0) {
				$url = $url->withPath($url->getPath(), $url->getBasePath() . $this->path);
			} elseif ($relativePath . '/' === $this->path) {
				$url = $url->withPath($url->getPath() . '/');
			} else {
				return null;
			}

			$httpRequest = $httpRequest->withUrl($url);
		}

		return $httpRequest;
	}


	protected function completeParameters(array $params): ?array
	{
		return $params;
	}


	/**
	 * Constructs absolute URL from array.
	 */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		if ($this->domain) {
			if (!isset($this->refUrlCache[$refUrl])) {
				$this->refUrlCache[$refUrl] = $refUrl->withHost(
					$this->expandDomain($refUrl->getHost()),
				);
			}

			$refUrl = $this->refUrlCache[$refUrl];
		}

		if ($this->path) {
			if (!isset($this->refUrlCache[$refUrl])) {
				$this->refUrlCache[$refUrl] = $refUrl->withPath($refUrl->getBasePath() . $this->path);
			}

			$refUrl = $this->refUrlCache[$refUrl];
		}

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
		foreach ($this->list as [$router, $oneWay]) {
			if ($oneWay) {
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
	 */
	public function add(Router $router, int $oneWay = 0): static
	{
		$this->list[] = [$router, $oneWay];
		$this->ranks = null;
		return $this;
	}


	/**
	 * Prepends a router.
	 */
	public function prepend(Router $router, int $oneWay = 0): void
	{
		array_splice($this->list, 0, 0, [[$router, $oneWay]]);
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
	public function addRoute(string $mask, array $metadata = [], int $oneWay = 0)
	{
		$this->add(new Route($mask, $metadata), $oneWay);
		return $this;
	}


	/**
	 * Returns an iterator over all routers.
	 */
	public function withDomain(string $domain): static
	{
		$router = new static;
		$router->domain = $domain;
		$router->refUrlCache = new \SplObjectStorage;
		$router->parent = $this;
		$this->add($router);
		return $router;
	}


	public function withPath(string $path): static
	{
		$router = new static;
		$router->path = rtrim($path, '/') . '/';
		$router->refUrlCache = new \SplObjectStorage;
		$router->parent = $this;
		$this->add($router);
		return $router;
	}


	public function end(): ?self
	{
		return $this->parent;
	}


	/**
	 * @return Router[]
	 */
	public function getRouters(): array
	{
		return array_column($this->list, 0);
	}


	/**
	 * @return int[]
	 */
	public function getFlags(): array
	{
		return array_column($this->list, 1);
	}


	public function getDomain(): ?string
	{
		return $this->domain;
	}


	public function getPath(): ?string
	{
		return $this->path;
	}


	private function expandDomain(string $host): string
	{
		$parts = ip2long($host) ? [$host] : array_reverse(explode('.', $host));
		return strtr($this->domain, [
			'%tld%' => $parts[0],
			'%domain%' => isset($parts[1]) ? "$parts[1].$parts[0]" : $parts[0],
			'%sld%' => $parts[1] ?? '',
		]);
	}
}
