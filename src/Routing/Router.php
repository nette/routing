<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Routing;

use Nette;


/**
 * Bidirectional router converting between HTTP requests and parameter arrays.
 */
interface Router
{
	/** @deprecated for backward compatibility */
	public const ONE_WAY = 0b0001;

	/**
	 * Matches an HTTP request and returns its parameters, or null if the route does not match.
	 * @return ?array<string, mixed>
	 */
	function match(Nette\Http\IRequest $httpRequest): ?array;

	/**
	 * Constructs an absolute URL from parameters, or null if the route cannot generate it.
	 * @param array<string, mixed>  $params
	 */
	function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string;
}
