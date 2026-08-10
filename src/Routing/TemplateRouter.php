<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Routing;

use Nette;


/**
 * Router capable of producing a URL template for parameters given as TemplateRouter::Deferred;
 * null means the request is not the router's or the template cannot be built.
 */
interface TemplateRouter
{
	/** marker for a parameter whose value will be filled into the template later */
	public const Deferred = Deferred;

	/** @param array<string, mixed> $params */
	function constructTemplate(array $params, Nette\Http\UrlScript $refUrl): ?LinkTemplate;
}

/** @internal */
const Deferred = new \stdClass;
