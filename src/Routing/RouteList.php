<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Routing;

use Nette;
use function array_column, array_filter, array_keys, array_reverse, array_splice, count, explode, ip2long, is_scalar, rtrim, strtr;


/**
 * Router collection that tries each router in sequence and caches URL construction lookups.
 */
class RouteList implements Router
{
	protected ?self $parent;

	/** @var list<array{Router, bool}> */
	private array $list = [];

	/** @var array<string, list<Router>>|null */
	private ?array $ranks = null;
	private ?string $cacheKey;
	private ?string $domain = null;
	private ?string $path = null;

	/** @var \SplObjectStorage<Nette\Http\UrlScript, Nette\Http\UrlScript> */
	private \SplObjectStorage $refUrlCache;


	public function __construct()
	{
		$this->refUrlCache = new \SplObjectStorage;
	}


	/**
	 * @return ?array<string, mixed>
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
			if (str_starts_with($relativePath, $this->path)) {
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


	/**
	 * @param array<string, mixed>  $params
	 * @return ?array<string, mixed>
	 */
	protected function completeParameters(array $params): ?array
	{
		return $params;
	}


	/** @param array<string, mixed>  $params */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		if ($this->domain) {
			if (!$this->refUrlCache->offsetExists($refUrl)) {
				$this->refUrlCache->offsetSet($refUrl, $refUrl->withHost(
					$this->expandDomain($refUrl->getHost()),
				));
			}

			$refUrl = $this->refUrlCache->offsetGet($refUrl);
		}

		if ($this->path) {
			if (!$this->refUrlCache->offsetExists($refUrl)) {
				$this->refUrlCache->offsetSet($refUrl, $refUrl->withPath($refUrl->getBasePath() . $this->path));
			}

			$refUrl = $this->refUrlCache->offsetGet($refUrl);
		}

		if ($this->ranks === null) {
			$this->warmupCache();
		}

		assert($this->ranks !== null);
		$key = $params[$this->cacheKey ?? ''] ?? null;
		$key = is_scalar($key) ? (string) $key : '*';
		if (!isset($this->ranks[$key])) {
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


	/**
	 * Builds an internal lookup index of routers grouped by their most discriminating constant parameter.
	 * Call this before URL generation to improve performance; called automatically on first use.
	 */
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

			foreach (array_filter($params, is_scalar(...)) as $name => $value) {
				$candidates[$name][(string) $value] = true;
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
			$value = $params[$this->cacheKey ?? ''] ?? null;
			$values = $value === null
				? array_keys($ranks)
				: [is_scalar($value) ? (string) $value : '*'];

			foreach ($values as $value) {
				$value = (string) $value;
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
	public function add(Router $router, bool $oneWay = false): static
	{
		$this->list[] = [$router, $oneWay];
		$this->ranks = null;
		return $this;
	}


	/**
	 * Prepends a router.
	 */
	public function prepend(Router $router, bool $oneWay = false): void
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
			$this->list[$index] = [$router, false];
		} else {
			array_splice($this->list, $index, 1);
		}

		$this->ranks = null;
	}


	/**
	 * Creates a Route from the mask and adds it to the list.
	 * @param string  $mask e.g. '<presenter>/<action>/<id \d{1,3}>'
	 * @param array<string, mixed>  $metadata default values or metadata
	 */
	public function addRoute(string $mask, array $metadata = [], bool $oneWay = false): static
	{
		$this->add(new Route($mask, $metadata), $oneWay);
		return $this;
	}


	/**
	 * Creates a child RouteList scoped to the given domain and adds it to this list.
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


	/**
	 * Creates a child RouteList scoped to the given path prefix and adds it to this list.
	 */
	public function withPath(string $path): static
	{
		$router = new static;
		$router->path = rtrim($path, '/') . '/';
		$router->refUrlCache = new \SplObjectStorage;
		$router->parent = $this;
		$this->add($router);
		return $router;
	}


	/**
	 * Returns the parent RouteList, used to end a withDomain()/withPath() chain.
	 */
	public function end(): ?self
	{
		return $this->parent;
	}


	/**
	 * Returns all routers in this list.
	 * @return list<Router>
	 */
	public function getRouters(): array
	{
		return array_column($this->list, 0);
	}


	/**
	 * Returns the flags (e.g. oneWay) for each router in this list.
	 * @return list<array{oneWay: bool}>
	 */
	public function getFlags(): array
	{
		return array_map(fn($info) => ['oneWay' => (bool) $info[1]], $this->list);
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
		assert($this->domain !== null);
		$parts = ip2long($host) ? [$host] : array_reverse(explode('.', $host));
		return strtr($this->domain, [
			'%tld%' => $parts[0],
			'%domain%' => isset($parts[1]) ? "$parts[1].$parts[0]" : $parts[0],
			'%sld%' => $parts[1] ?? '',
		]);
	}
}
