<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Routing;

use Nette;
use function ini_get, is_scalar;


/**
 * The bidirectional route for trivial routing via query parameters.
 */
class SimpleRouter implements Router, TemplateRouter
{
	public function __construct(
		/** @var array<string, mixed> */
		private readonly array $defaults = [],
	) {
	}


	/** @return ?array<string, mixed> */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		return $httpRequest->getUrl()->getPathInfo() === ''
			? $httpRequest->getQuery() + $this->defaults
			: null;
	}


	/** @param array<string, mixed>  $params */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		$params = $this->removeDefaults($params);

		foreach ($params as $key => $value) {
			if ($value === TemplateRouter::Deferred) {
				throw new Nette\InvalidArgumentException("Parameter \$$key is deferred, use constructTemplate() to build a link template.");
			}
		}

		$url = $refUrl->getHostUrl() . $refUrl->getPath();
		$sep = ini_get('arg_separator.input');
		$query = http_build_query(Route::objectsToArrays($params), '', $sep ? $sep[0] : '&');
		if ($query !== '') {
			$url .= '?' . $query;
		}

		return $url;
	}


	/** @param array<string, mixed>  $params */
	public function constructTemplate(array $params, Nette\Http\UrlScript $refUrl): ?LinkTemplate
	{
		$params = $this->removeDefaults($params);

		$sep = ini_get('arg_separator.input');
		$sep = $sep ? $sep[0] : '&';
		$parts = [$refUrl->getHostUrl() . $refUrl->getPath()];
		foreach ($params as $key => $value) {
			if ($value === TemplateRouter::Deferred) {
				$parts[] = LinkTemplate::queryVariable((string) $key, (string) $key);
			} elseif ($pair = LinkTemplate::queryPair($key, $value, $sep)) {
				$parts[] = $pair;
			}
		}

		return LinkTemplate::fromParts($parts, $sep);
	}


	/**
	 * Removes default values; null values are retained, markers never equal a default.
	 * @param  array<string, mixed>  $params
	 * @return array<string, mixed>
	 */
	private function removeDefaults(array $params): array
	{
		foreach ($this->defaults as $key => $value) {
			if (isset($params[$key])
				&& (is_scalar($params[$key]) ? (string) $params[$key] : $params[$key]) === (is_scalar($value) ? (string) $value : $value)
			) {
				unset($params[$key]);
			}
		}

		return $params;
	}


	/** @return array<string, mixed> */
	public function getDefaults(): array
	{
		return $this->defaults;
	}
}
