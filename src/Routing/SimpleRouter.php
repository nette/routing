<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Routing;

use Nette;


/**
 * The bidirectional route for trivial routing via query parameters.
 */
class SimpleRouter implements Router
{
	use Nette\SmartObject;

	/** @var array */
	private $defaults;


	public function __construct(array $defaults = [])
	{
		$this->defaults = $defaults;
	}


	/**
	 * Maps HTTP request to an array.
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		return $httpRequest->getUrl()->getPathInfo() === ''
			? $httpRequest->getQuery() + $this->defaults
			: null;
	}


	/**
	 * Constructs absolute URL from array.
	 */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		// remove default values; null values are retain
		foreach ($this->defaults as $key => $value) {
			if (isset($params[$key]) && $params[$key] == $value) { // intentionally ==
				unset($params[$key]);
			}
		}

		$url = $refUrl->getHostUrl() . $refUrl->getPath();
		$sep = ini_get('arg_separator.input');
		$query = http_build_query($params, '', $sep ? $sep[0] : '&');
		if ($query != '') { // intentionally ==
			$url .= '?' . $query;
		}
		return $url;
	}


	/**
	 * Returns default values.
	 */
	public function getDefaults(): array
	{
		return $this->defaults;
	}
}
