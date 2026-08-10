<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Routing;

use Nette;
use Nette\Utils\Strings;
use function array_key_exists, count, ini_get, is_array, is_object, is_scalar, is_string, strlen;


/**
 * Bidirectional route mapping between HTTP requests and parameter arrays using a URL mask.
 */
class Route implements Router, TemplateRouter
{
	/** key used in metadata */
	public const
		Value = 'value',
		Pattern = 'pattern',
		FilterIn = 'filterIn',
		FilterOut = 'filterOut',
		FilterTable = 'filterTable',
		FilterStrict = 'filterStrict';

	/** @deprecated use Route::Value */
	public const VALUE = self::Value;

	/** @deprecated use Route::Pattern */
	public const PATTERN = self::Pattern;

	/** @deprecated use Route::FilterIn */
	public const FILTER_IN = self::FilterIn;

	/** @deprecated use Route::FilterOut */
	public const FILTER_OUT = self::FilterOut;

	/** @deprecated use Route::FilterTable */
	public const FILTER_TABLE = self::FilterTable;

	/** @deprecated use Route::FilterStrict */
	public const FILTER_STRICT = self::FilterStrict;

	/** key used in metadata */
	private const
		Default = 'defOut',
		Fixity = 'fixity',
		FilterTableOut = 'filterTO';

	/** url type */
	private const
		Host = 1,
		Path = 2,
		Relative = 3;

	/** fixity types - has default value and is: */
	private const
		InQuery = 0,
		InPath = 1, // in brackets is default value = null
		Constant = 2;

	/** @var array<string, array<string, mixed>> */
	protected array $defaultMeta = [
		'#' => [ // default style for path parameters
			self::Pattern => '[^/]+',
			self::FilterOut => [self::class, 'param2path'],
		],
	];

	/** @var list<string> */
	private array $sequence;

	/** regular expression pattern */
	private string $re;

	/** @var array<string, string> parameter aliases in regular expression */
	private array $aliases = [];

	/** @var array<string, array<string, mixed>> of [value & fixity, filterIn, filterOut] */
	private array $metadata = [];

	/** @var array<string, string> */
	private array $xlat = [];

	/** Host, Path, Relative */
	private int $type;

	/** http | https */
	private string $scheme = '';


	/**
	 * @param array<string, mixed>  $metadata default values or metadata
	 */
	public function __construct(
		/** e.g. '<presenter>/<action>/<id \d{1,3}>' */
		private string $mask,
		array $metadata = [],
	) {
		$this->metadata = $this->normalizeMetadata($metadata);
		$this->parseMask($this->detectMaskType());
	}


	public function getMask(): string
	{
		return $this->mask;
	}


	/**
	 * @internal
	 * @return array<string, array<string, mixed>>
	 */
	protected function getMetadata(): array
	{
		return $this->metadata;
	}


	/** @return array<string, mixed> */
	public function getDefaults(): array
	{
		$defaults = [];
		foreach ($this->metadata as $name => $meta) {
			if (isset($meta[self::Fixity])) {
				$defaults[$name] = $meta[self::Value];
			}
		}

		return $defaults;
	}


	/**
	 * Returns parameters that must have a specific fixed value for the route to match.
	 * @internal
	 * @return array<string, mixed>
	 */
	public function getConstantParameters(): array
	{
		$res = [];
		foreach ($this->metadata as $name => $meta) {
			if (isset($meta[self::Fixity]) && $meta[self::Fixity] === self::Constant) {
				$res[$name] = $meta[self::Value];
			}
		}

		return $res;
	}


	/** @return ?array<string, mixed> */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		// combine with precedence: mask (params in URL-path), fixity, query, (post,) defaults

		// 1) URL MASK
		$url = $httpRequest->getUrl();
		$re = $this->re;

		if ($this->type === self::Host) {
			$path = '//' . $url->getHost() . $url->getPath();
			$re = strtr($re, self::wildcardTable($url, quote: true));

		} elseif ($this->type === self::Relative) {
			$basePath = $url->getBasePath();
			if (!str_starts_with($url->getPath(), $basePath)) {
				return null;
			}

			$path = substr($url->getPath(), strlen($basePath));

		} else {
			$path = $url->getPath();
		}

		$path = rawurldecode($path);
		if ($path !== '' && $path[-1] !== '/') {
			$path .= '/';
		}

		if (!$matches = Strings::match($path, $re)) {
			return null; // stop, not matched
		}

		// assigns matched values to parameters
		$params = [];
		foreach ($matches as $k => $v) {
			if (is_string($k) && $v !== '') {
				$params[$this->aliases[$k]] = $v;
			}
		}

		// 2) CONSTANT FIXITY
		foreach ($this->metadata as $name => $meta) {
			if (!isset($params[$name]) && isset($meta[self::Fixity]) && $meta[self::Fixity] !== self::InQuery) {
				$params[$name] = null; // cannot be overwriten in 3) and detected by isset() in 4)
			}
		}

		// 3) QUERY
		$params += self::renameKeys($httpRequest->getQuery(), array_flip($this->xlat));

		// 4) APPLY FILTERS & FIXITY
		foreach ($this->metadata as $name => $meta) {
			if (isset($params[$name])) {
				if (!is_scalar($params[$name])) {
					// do nothing
				} elseif (isset($meta[self::FilterTable][$params[$name]])) { // applies filterTable only to scalar parameters
					$params[$name] = $meta[self::FilterTable][$params[$name]];

				} elseif (isset($meta[self::FilterTable]) && !empty($meta[self::FilterStrict])) {
					return null; // rejected by filterTable

				} elseif (isset($meta[self::FilterIn])) { // applies filterIn only to scalar parameters
					$params[$name] = $meta[self::FilterIn]((string) $params[$name]);
					if ($params[$name] === null && !isset($meta[self::Fixity])) {
						return null; // rejected by filter
					}
				}
			} elseif (isset($meta[self::Fixity])) {
				$params[$name] = $meta[self::Value];
			}
		}

		if (isset($this->metadata[''][self::FilterIn])) {
			$params = $this->metadata[''][self::FilterIn]($params);
			if ($params === null) {
				return null;
			}
		}

		return $params;
	}


	/** @param array<string, mixed>  $params */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		if (($params = $this->prepareParameters($params)) === null) {
			return null;
		}

		if (!$this->preprocessParams($params)) {
			return null;
		}

		$url = $this->compileUrl($params);
		if ($url === null) {
			return null;
		}

		// absolutize
		$url = $this->type === self::Host
			? strtr($url, self::wildcardTable($refUrl))
			: $this->urlPrefix($refUrl) . $url;
		$url = ($this->scheme ?: $refUrl->getScheme()) . ':' . $url;

		// build query string
		$params = self::renameKeys($params, $this->xlat);
		$sep = ini_get('arg_separator.input');
		$query = http_build_query(self::objectsToArrays($params), '', $sep ? $sep[0] : '&');
		if ($query !== '') {
			$url .= '?' . $query;
		}

		return $url;
	}


	/**
	 * Adjusts the parameters before URL and template generation.
	 * Returning null means the parameters are not this route's.
	 * @param  array<string, mixed>  $params
	 * @return ?array<string, mixed>
	 */
	protected function prepareParameters(array $params): ?array
	{
		return $params;
	}


	/** @param array<string, mixed>  $params */
	private function preprocessParams(array &$params, bool $deferrable = false): bool
	{
		$filter = $this->metadata[''][self::FilterOut] ?? null;
		if ($filter) {
			$params = $filter($params);
			if ($params === null) {
				return false; // rejected by global filter
			}
		}

		foreach ($this->metadata as $name => $meta) {
			$fixity = $meta[self::Fixity] ?? null;

			if (!isset($params[$name])) {
				continue; // retains null values
			}

			if (is_scalar($params[$name])) {
				$params[$name] = $params[$name] === false
					? '0'
					: (string) $params[$name];
			}

			if ($fixity !== null) {
				if ($params[$name] === $meta[self::Value]) { // remove default values; null values are retain
					unset($params[$name]);
					continue;

				} elseif ($fixity === self::Constant) {
					return false; // wrong parameter value
				}
			}

			if ($params[$name] === TemplateRouter::Deferred) { // filters & pattern are enforced on arrival instead
				if (!$deferrable) {
					throw new Nette\InvalidArgumentException("Parameter \$$name is deferred, use constructTemplate() to build a link template.");
				}

				continue;
			}

			if (is_scalar($params[$name]) && isset($meta[self::FilterTableOut][$params[$name]])) {
				$params[$name] = $meta[self::FilterTableOut][$params[$name]];

			} elseif (isset($meta[self::FilterTableOut]) && !empty($meta[self::FilterStrict])) {
				return false;

			} elseif (isset($meta[self::FilterOut])) {
				if (!is_scalar($params[$name]) && $meta[self::FilterOut] === ($this->defaultMeta['#'][self::FilterOut] ?? null)) {
					return false; // the default filter cannot convert a non-scalar value to a path segment
				}

				$params[$name] = $meta[self::FilterOut]($params[$name]);
			}

			if (
				isset($meta[self::Pattern])
				&& (!is_scalar($params[$name])
					|| !preg_match("#(?:{$meta[self::Pattern]})$#DA", rawurldecode((string) $params[$name])))
			) {
				return false; // pattern not match
			}
		}

		if (!$deferrable) {
			foreach ($params as $name => $value) {
				if ($value === TemplateRouter::Deferred) { // parameters outside the mask
					throw new Nette\InvalidArgumentException("Parameter \$$name is deferred, use constructTemplate() to build a link template.");
				}
			}
		}

		return true;
	}


	/** @param array<string, mixed>  $params */
	private function compileUrl(array &$params): ?string
	{
		$brackets = [];
		$required = null; // null for auto-optional
		$path = '';
		$i = count($this->sequence) - 1;

		do {
			$path = $this->sequence[$i] . $path;
			if ($i === 0) {
				return $path;
			}

			$i--;

			$name = $this->sequence[$i--]; // parameter name

			if ($name === ']') { // opening optional part
				$brackets[] = $path;

			} elseif ($name[0] === '[') { // closing optional part
				$tmp = array_pop($brackets);
				if ($required < count($brackets) + 1) { // is this level optional?
					if ($name !== '[!') { // and not "required"-optional
						$path = $tmp;
					}
				} else {
					$required = count($brackets);
				}
			} elseif ($name[0] === '?') { // "foo" parameter
				continue;

			} elseif (isset($params[$name]) && $params[$name] !== '') {
				$required = count($brackets); // make this level required
				$path = $params[$name] . $path;
				unset($params[$name]);

			} elseif (isset($this->metadata[$name][self::Fixity])) { // has default value?
				$path = $required === null && !$brackets // auto-optional
					? ''
					: $this->metadata[$name][self::Default] . $path;

			} else {
				return null; // missing parameter '$name'
			}
		} while (true);
	}


	/**
	 * Mirror of compileUrl() emitting a part list with template variables for
	 * deferred parameters; the list is in reverse order. Equivalence of both walks
	 * is pinned by tests comparing expand() with constructUrl() byte for byte.
	 * @param  array<string, mixed>  $params
	 * @return ?list<string|array{name: string, key: null, encode: \Closure(string): string}>
	 */
	private function compileParts(array &$params): ?array
	{
		$parts = []; // in reverse order
		$brackets = []; // stack of counts of $parts
		$required = null; // null for auto-optional
		$i = count($this->sequence) - 1;

		do {
			if ($this->sequence[$i] !== '') {
				$parts[] = $this->sequence[$i];
			}

			if ($i === 0) {
				return $parts;
			}

			$i--;

			$name = $this->sequence[$i--]; // parameter name

			if ($name === ']') { // opening optional part
				$brackets[] = count($parts);

			} elseif ($name[0] === '[') { // closing optional part
				$tmp = array_pop($brackets) ?? throw new Nette\InvalidStateException('Unbalanced brackets.');
				if ($required < count($brackets) + 1) { // is this level optional?
					if ($name !== '[!') { // and not "required"-optional
						array_splice($parts, $tmp);
					}
				} else {
					$required = count($brackets);
				}
			} elseif ($name[0] === '?') { // "foo" parameter
				continue;

			} elseif (isset($params[$name]) && $params[$name] !== '') {
				$required = count($brackets); // make this level required
				$parts[] = $params[$name] === TemplateRouter::Deferred
					? LinkTemplate::pathVariable($name, $this->buildValueHandler($name))
					: (string) $params[$name];
				unset($params[$name]);

			} elseif (isset($this->metadata[$name][self::Fixity])) { // has default value?
				if ($required === null && !$brackets) { // auto-optional
					$parts = [];
				} elseif (($default = (string) $this->metadata[$name][self::Default]) !== '') {
					$parts[] = $default;
				}
			} else {
				return null; // missing parameter '$name'
			}
		} while (true);
	}


	private function detectMaskType(): string
	{
		// '//host/path' vs. '/abs. path' vs. 'relative path'
		if (preg_match('#(?:(https?):)?(//.*)#A', $this->mask, $m)) {
			$this->type = self::Host;
			[, $this->scheme, $path] = $m;
			return $path;

		} elseif (str_starts_with($this->mask, '/')) {
			$this->type = self::Path;

		} else {
			$this->type = self::Relative;
		}

		return $this->mask;
	}


	/**
	 * @param array<string, mixed>  $metadata
	 * @return array<string, array<string, mixed>>
	 */
	private function normalizeMetadata(array $metadata): array
	{
		foreach ($metadata as $name => $meta) {
			if (!is_array($meta)) {
				$metadata[$name] = $meta = [self::Value => $meta];
			}

			if (array_key_exists(self::Value, $meta)) {
				if (is_scalar($meta[self::Value])) {
					$metadata[$name][self::Value] = $meta[self::Value] === false
						? '0'
						: (string) $meta[self::Value];
				}

				$metadata[$name]['fixity'] = self::Constant;
			}
		}

		return $metadata;
	}


	private function parseMask(string $path): void
	{
		// <parameter-name[=default] [pattern]> or [ or ] or ?...
		$parts = Strings::split($path, '/<([^<>= ]+)(=[^<> ]*)? *([^<>]*)>|(\[!?|]|\s*\?.*)/');

		$i = count($parts) - 1;
		if ($i === 0) {
			$this->re = '#' . preg_quote($parts[0], '#') . '/?$#DA';
			$this->sequence = [$parts[0]];
			return;
		}

		if ($this->parseQuery($parts)) {
			$i -= 5;
		}

		$brackets = 0; // optional level
		$re = '';
		$sequence = [];
		$autoOptional = true;

		do {
			$part = $parts[$i]; // part of path
			if (strpbrk($part, '<>') !== false) {
				throw new Nette\InvalidArgumentException("Unexpected '$part' in mask '$this->mask'.");
			}

			array_unshift($sequence, $part);
			$re = preg_quote($part, '#') . $re;
			if ($i === 0) {
				break;
			}

			$i--;

			$part = $parts[$i]; // [ or ]
			if ($part === '[' || $part === ']' || $part === '[!') {
				$brackets += $part[0] === '[' ? -1 : 1;
				if ($brackets < 0) {
					throw new Nette\InvalidArgumentException("Unexpected '$part' in mask '$this->mask'.");
				}

				array_unshift($sequence, $part);
				$re = ($part[0] === '[' ? '(?:' : ')?') . $re;
				$i -= 4;
				continue;
			}

			$pattern = trim($parts[$i--]); // validation condition (as regexp)
			$default = $parts[$i--]; // default value
			$name = $parts[$i--]; // parameter name
			array_unshift($sequence, $name);

			if ($name[0] === '?') { // "foo" parameter
				$name = substr($name, 1);
				$re = $pattern
					? '(?:' . preg_quote($name, '#') . "|$pattern)$re"
					: preg_quote($name, '#') . $re;
				$sequence[1] = $name . $sequence[1];
				continue;
			}

			// pattern, condition & metadata
			$meta = ($this->metadata[$name] ?? []) + ($this->defaultMeta[$name] ?? $this->defaultMeta['#']);

			if ($pattern === '' && isset($meta[self::Pattern])) {
				$pattern = $meta[self::Pattern];
			}

			if ($default !== '') {
				$meta[self::Value] = substr($default, 1);
				$meta[self::Fixity] = self::InPath;
			}

			$meta[self::FilterTableOut] = empty($meta[self::FilterTable])
				? null
				: array_flip($meta[self::FilterTable]);
			if (array_key_exists(self::Value, $meta)) {
				if (isset($meta[self::FilterTableOut][$meta[self::Value]])) {
					$meta[self::Default] = $meta[self::FilterTableOut][$meta[self::Value]];

				} elseif (isset($meta[self::Value], $meta[self::FilterOut])) {
					$meta[self::Default] = $meta[self::FilterOut]($meta[self::Value]);

				} else {
					$meta[self::Default] = $meta[self::Value];
				}
			}

			$meta[self::Pattern] = $pattern;

			// include in expression
			$this->aliases['p' . $i] = $name;
			$re = '(?P<p' . $i . '>(?U)' . $pattern . ')' . $re;
			if ($brackets) { // is in brackets?
				if (!isset($meta[self::Value])) {
					$meta[self::Value] = $meta[self::Default] = null;
				}

				$meta[self::Fixity] = self::InPath;

			} elseif (isset($meta[self::Fixity])) {
				if ($autoOptional) {
					$re = '(?:' . $re . ')?';
				}

				$meta[self::Fixity] = self::InPath;

			} else {
				$autoOptional = false;
			}

			$this->metadata[$name] = $meta;
		} while (true);

		if ($brackets) {
			throw new Nette\InvalidArgumentException("Missing '[' in mask '$this->mask'.");
		}

		$this->re = '#' . $re . '/?$#DA';
		$this->sequence = $sequence;
	}


	/** @param list<string>  $parts */
	private function parseQuery(array $parts): bool
	{
		$query = $parts[count($parts) - 2] ?? '';
		if (!str_starts_with(ltrim($query), '?')) {
			return false;
		}

		// name=<parameter-name [pattern]>
		$matches = Strings::matchAll($query, '/(?:([a-zA-Z0-9_.-]+)=)?<([^> ]+) *([^>]*)>/');

		foreach ($matches as [, $param, $name, $pattern]) { // $pattern is not used
			$meta = ($this->metadata[$name] ?? []) + ($this->defaultMeta['?' . $name] ?? []);

			if (array_key_exists(self::Value, $meta)) {
				$meta[self::Fixity] = self::InQuery;
			}

			unset($meta[self::Pattern]);
			$meta[self::FilterTableOut] = empty($meta[self::FilterTable])
				? null
				: array_flip($meta[self::FilterTable]);

			$this->metadata[$name] = $meta;
			if ($param !== '') {
				$this->xlat[$name] = $param;
			}
		}

		return true;
	}


	/********************* Utilities ****************d*g**/


	/**
	 * Renames keys in array according to the translation table.
	 * @param array<string, mixed>  $arr
	 * @param array<string, string>  $xlat
	 * @return array<string, mixed>
	 */
	private static function renameKeys(array $arr, array $xlat): array
	{
		if (!$xlat) {
			return $arr;
		}

		$res = [];
		$occupied = array_flip($xlat);
		foreach ($arr as $k => $v) {
			if (isset($xlat[$k])) {
				$res[$xlat[$k]] = $v;

			} elseif (!isset($occupied[$k])) {
				$res[$k] = $v;
			}
		}

		return $res;
	}


	/**
	 * Converts objects to arrays of their public properties, as http_build_query() did on its own before PHP 8.6.
	 * @internal
	 * @param array<mixed>  $arr
	 * @return array<mixed>
	 */
	public static function objectsToArrays(array $arr): array
	{
		foreach ($arr as &$v) {
			if (is_object($v)) {
				$v = get_object_vars($v);
			}
			if (is_array($v)) {
				$v = self::objectsToArrays($v);
			}
		}

		return $arr;
	}


	/**
	 * Splits a host into parts for %tld%/%domain%/%sld% wildcards; IP addresses are kept whole.
	 * @internal
	 * @return non-empty-list<string>
	 */
	public static function hostParts(string $host): array
	{
		return str_starts_with($host, '[') || ip2long($host) !== false // [IPv6] or IPv4
			? [$host]
			: array_reverse(explode('.', $host));
	}


	/**
	 * Replacement table for the %host% family wildcards.
	 * @return array<string, string>
	 */
	private static function wildcardTable(Nette\Http\UrlScript $url, bool $quote = false): array
	{
		$host = $url->getHost();
		$parts = self::hostParts($host);
		$table = [
			'/%basePath%/' => $url->getBasePath(),
			'%tld%' => $parts[0],
			'%domain%' => isset($parts[1]) ? "$parts[1].$parts[0]" : $parts[0],
			'%sld%' => $parts[1] ?? '',
			'%host%' => $host,
		];
		return $quote
			? array_map(fn(string $s) => preg_quote($s, '#'), $table)
			: $table;
	}


	/**
	 * Authority and base-path prefix for non-host masks.
	 */
	private function urlPrefix(Nette\Http\UrlScript $refUrl): string
	{
		$prefix = ($tmp = $refUrl->getAuthority()) ? "//$tmp" : '';
		return $this->type === self::Relative
			? $prefix . $refUrl->getBasePath()
			: $prefix;
	}


	/**
	 * Constructs a URL template for parameters given as TemplateRouter::Deferred;
	 * variables carry value handlers mirroring this route's filter tables and filters.
	 * @param  array<string, mixed>  $params
	 */
	public function constructTemplate(array $params, Nette\Http\UrlScript $refUrl): ?LinkTemplate
	{
		if (($params = $this->prepareParameters($params)) === null) {
			return null;
		}

		$deferred = [];
		foreach ($params as $name => $value) {
			if ($value === TemplateRouter::Deferred) {
				$deferred[$name] = true;
			}
		}

		if (!$this->preprocessParams($params, deferrable: true)) {
			return null;
		}

		$parts = $this->compileParts($params);
		if ($parts === null) {
			return null;
		}

		$parts = array_reverse($parts);

		// absolutize; wildcards appear only in mask literals
		$head = ($this->scheme ?: $refUrl->getScheme()) . ':';
		if ($this->type === self::Host) {
			$table = self::wildcardTable($refUrl);
			foreach ($parts as &$part) {
				if (is_string($part)) {
					$part = strtr($part, $table);
				}
			}

			unset($part);

		} else {
			$head .= $this->urlPrefix($refUrl);
		}
		if (is_string($parts[0] ?? null)) {
			$parts[0] = $head . $parts[0];
		} else {
			array_unshift($parts, $head);
		}

		foreach ($parts as $part) {
			if (!is_string($part)) {
				unset($deferred[$part['name']]);
			}
		}

		// query pairs and variables; punctuation is added by the template at expansion time
		$sep = ini_get('arg_separator.input');
		$sep = $sep ? $sep[0] : '&';
		$occupied = array_flip($this->xlat);
		foreach ($params as $name => $value) {
			if (isset($this->xlat[$name])) {
				$key = $this->xlat[$name];
			} elseif (isset($occupied[$name])) {
				continue;
			} else {
				$key = $name;
			}

			if ($value === TemplateRouter::Deferred) {
				$parts[] = LinkTemplate::queryVariable($name, $key, $this->buildValueHandler($name));
				unset($deferred[$name]);

			} elseif ($pair = LinkTemplate::queryPair($key, $value, $sep)) {
				$parts[] = $pair;
			}
		}

		if ($deferred) {
			$name = array_key_first($deferred);
			throw new Nette\InvalidArgumentException("Parameter \$$name cannot be deferred, the route does not pass its value through to the URL.");
		}

		return LinkTemplate::fromParts($parts, $sep);
	}


	/**
	 * Builds a fill-time mirror of preprocessParams() for one parameter, or null
	 * when the value passes through untransformed (the template encodes it itself).
	 * @return ?\Closure(string): string
	 */
	private function buildValueHandler(string $name): ?\Closure
	{
		$meta = $this->metadata[$name] ?? null;
		if ($meta === null) {
			return null; // parameter outside the mask
		}

		$table = $meta[self::FilterTableOut] ?? null;
		$strict = !empty($meta[self::FilterStrict]);
		$filter = $meta[self::FilterOut] ?? null;
		if ($table === null && ($filter === null || $filter === ($this->defaultMeta['#'][self::FilterOut] ?? null))) {
			return null;
		}

		$inPath = isset($meta[self::Pattern]); // query parameters have no pattern
		return function (string $value) use ($table, $strict, $filter, $inPath, $name): string {
			if ($table !== null && isset($table[$value])) {
				return (string) $table[$value];
			} elseif ($table !== null && $strict) {
				throw new Nette\InvalidArgumentException("Value '$value' of parameter \$$name not found in filter table.");
			} elseif ($filter !== null) {
				return (string) $filter($value);
			}

			return $inPath ? self::param2path($value) : $value;
		};
	}


	public static function param2path(string $s): string
	{
		// segment + "/", see https://datatracker.ietf.org/doc/html/rfc3986#appendix-A
		return (string) preg_replace_callback(
			'#[^\w.~!$&\'()*+,;=:@/-]#',
			fn($m) => rawurlencode($m[0]),
			$s,
		);
	}
}
