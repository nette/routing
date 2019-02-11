<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Routing;

use Nette;


/**
 * The bi-directional router.
 */
interface Router
{
	/** for back compatibility */
	public const ONE_WAY = 0b0001;

	/**
	 * Maps HTTP request to an array.
	 */
	function match(Nette\Http\IRequest $httpRequest): ?array;

	/**
	 * Constructs absolute URL from array.
	 */
	function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string;
}
