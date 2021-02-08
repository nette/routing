<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Routing;

use Nette;
use Nette\Utils\Strings;
use function array_key_exists, is_array, count, strlen;


/**
 * The bidirectional route is responsible for mapping
 * HTTP request to an array for dispatch and vice-versa.
 */
class Route implements Router
{
	use Nette\SmartObject;

	/** key used in metadata {@link Route::__construct} */
	public const
		VALUE = 'value',
		PATTERN = 'pattern',
		FILTER_IN = 'filterIn',
		FILTER_OUT = 'filterOut',
		FILTER_TABLE = 'filterTable',
		FILTER_STRICT = 'filterStrict';

	/** key used in metadata */
	private const
		DEFAULT = 'defOut',
		FIXITY = 'fixity',
		FILTER_TABLE_OUT = 'filterTO';

	/** url type */
	private const
		HOST = 1,
		PATH = 2,
		RELATIVE = 3;

	/** fixity types - has default value and is: */
	private const
		IN_QUERY = 0,
		IN_PATH = 1, // in brackets is default value = null
		CONSTANT = 2;

	/** @var array */
	protected $defaultMeta = [
		'#' => [ // default style for path parameters
			self::PATTERN => '[^/]+',
			self::FILTER_OUT => [self::class, 'param2path'],
		],
	];

	/** @var string */
	private $mask;

	/** @var array */
	private $sequence;

	/** @var string  regular expression pattern */
	private $re;

	/** @var string[]  parameter aliases in regular expression */
	private $aliases = [];

	/** @var array of [value & fixity, filterIn, filterOut] */
	private $metadata = [];

	/** @var array  */
	private $xlat = [];

	/** @var int HOST, PATH, RELATIVE */
	private $type;

	/** @var string  http | https */
	private $scheme;


	/**
	 * @param  string  $mask  e.g. '<presenter>/<action>/<id \d{1,3}>'
	 */
	public function __construct(string $mask, array $metadata = [])
	{
		$this->mask = $mask;
		$this->metadata = $this->normalizeMetadata($metadata);
		$this->parseMask($this->detectMaskType());
	}


	/**
	 * Returns mask.
	 */
	public function getMask(): string
	{
		return $this->mask;
	}


	/** @internal */
	protected function getMetadata(): array
	{
		return $this->metadata;
	}


	/**
	 * Returns default values.
	 */
	public function getDefaults(): array
	{
		$defaults = [];
		foreach ($this->metadata as $name => $meta) {
			if (isset($meta[self::FIXITY])) {
				$defaults[$name] = $meta[self::VALUE];
			}
		}

		return $defaults;
	}


	/** @internal */
	public function getConstantParameters(): array
	{
		$res = [];
		foreach ($this->metadata as $name => $meta) {
			if (isset($meta[self::FIXITY]) && $meta[self::FIXITY] === self::CONSTANT) {
				$res[$name] = $meta[self::VALUE];
			}
		}

		return $res;
	}


	/**
	 * Maps HTTP request to an array.
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		// combine with precedence: mask (params in URL-path), fixity, query, (post,) defaults

		// 1) URL MASK
		$url = $httpRequest->getUrl();
		$re = $this->re;

		if ($this->type === self::HOST) {
			$host = $url->getHost();
			$path = '//' . $host . $url->getPath();
			$parts = ip2long($host)
				? [$host]
				: array_reverse(explode('.', $host));
			$re = strtr($re, [
				'/%basePath%/' => preg_quote($url->getBasePath(), '#'),
				'%tld%' => preg_quote($parts[0], '#'),
				'%domain%' => preg_quote(isset($parts[1]) ? "$parts[1].$parts[0]" : $parts[0], '#'),
				'%sld%' => preg_quote($parts[1] ?? '', '#'),
				'%host%' => preg_quote($host, '#'),
			]);

		} elseif ($this->type === self::RELATIVE) {
			$basePath = $url->getBasePath();
			if (strncmp($url->getPath(), $basePath, strlen($basePath)) !== 0) {
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
			if (!isset($params[$name]) && isset($meta[self::FIXITY]) && $meta[self::FIXITY] !== self::IN_QUERY) {
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
				} elseif (isset($meta[self::FILTER_TABLE][$params[$name]])) { // applies filterTable only to scalar parameters
					$params[$name] = $meta[self::FILTER_TABLE][$params[$name]];

				} elseif (isset($meta[self::FILTER_TABLE]) && !empty($meta[self::FILTER_STRICT])) {
					return null; // rejected by filterTable

				} elseif (isset($meta[self::FILTER_IN])) { // applies filterIn only to scalar parameters
					$params[$name] = $meta[self::FILTER_IN]((string) $params[$name]);
					if ($params[$name] === null && !isset($meta[self::FIXITY])) {
						return null; // rejected by filter
					}
				}
			} elseif (isset($meta[self::FIXITY])) {
				$params[$name] = $meta[self::VALUE];
			}
		}

		if (isset($this->metadata[null][self::FILTER_IN])) {
			$params = $this->metadata[null][self::FILTER_IN]($params);
			if ($params === null) {
				return null;
			}
		}

		return $params;
	}


	/**
	 * Constructs absolute URL from array.
	 */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		if (!$this->preprocessParams($params)) {
			return null;
		}

		$url = $this->compileUrl($params);
		if ($url === null) {
			return null;
		}

		// absolutize
		if ($this->type === self::RELATIVE) {
			$url = (($tmp = $refUrl->getAuthority()) ? "//$tmp" : '') . $refUrl->getBasePath() . $url;

		} elseif ($this->type === self::PATH) {
			$url = (($tmp = $refUrl->getAuthority()) ? "//$tmp" : '') . $url;

		} else {
			$host = $refUrl->getHost();
			$parts = ip2long($host)
				? [$host]
				: array_reverse(explode('.', $host));
			$url = strtr($url, [
				'/%basePath%/' => $refUrl->getBasePath(),
				'%tld%' => $parts[0],
				'%domain%' => isset($parts[1]) ? "$parts[1].$parts[0]" : $parts[0],
				'%sld%' => $parts[1] ?? '',
				'%host%' => $host,
			]);
		}

		$url = ($this->scheme ?: $refUrl->getScheme()) . ':' . $url;

		// build query string
		$params = self::renameKeys($params, $this->xlat);
		$sep = ini_get('arg_separator.input');
		$query = http_build_query($params, '', $sep ? $sep[0] : '&');
		if ($query !== '') {
			$url .= '?' . $query;
		}

		return $url;
	}


	private function preprocessParams(array &$params): bool
	{
		$filter = $this->metadata[null][self::FILTER_OUT] ?? null;
		if ($filter) {
			$params = $filter($params);
			if ($params === null) {
				return false; // rejected by global filter
			}
		}

		foreach ($this->metadata as $name => $meta) {
			$fixity = $meta[self::FIXITY] ?? null;

			if (!isset($params[$name])) {
				continue; // retains null values
			}

			if (is_scalar($params[$name])) {
				$params[$name] = $params[$name] === false
					? '0'
					: (string) $params[$name];
			}

			if ($fixity !== null) {
				if ($params[$name] === $meta[self::VALUE]) { // remove default values; null values are retain
					unset($params[$name]);
					continue;

				} elseif ($fixity === self::CONSTANT) {
					return false; // wrong parameter value
				}
			}

			if (is_scalar($params[$name]) && isset($meta[self::FILTER_TABLE_OUT][$params[$name]])) {
				$params[$name] = $meta[self::FILTER_TABLE_OUT][$params[$name]];

			} elseif (isset($meta[self::FILTER_TABLE_OUT]) && !empty($meta[self::FILTER_STRICT])) {
				return false;

			} elseif (isset($meta[self::FILTER_OUT])) {
				$params[$name] = $meta[self::FILTER_OUT]($params[$name]);
			}

			if (
				isset($meta[self::PATTERN])
				&& !preg_match("#(?:{$meta[self::PATTERN]})$#DA", rawurldecode((string) $params[$name]))
			) {
				return false; // pattern not match
			}
		}

		return true;
	}


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

			} elseif (isset($this->metadata[$name][self::FIXITY])) { // has default value?
				$path = $required === null && !$brackets // auto-optional
					? ''
					: $this->metadata[$name][self::DEFAULT] . $path;

			} else {
				return null; // missing parameter '$name'
			}
		} while (true);
	}


	private function detectMaskType(): string
	{
		// '//host/path' vs. '/abs. path' vs. 'relative path'
		if (preg_match('#(?:(https?):)?(//.*)#A', $this->mask, $m)) {
			$this->type = self::HOST;
			[, $this->scheme, $path] = $m;
			return $path;

		} elseif (substr($this->mask, 0, 1) === '/') {
			$this->type = self::PATH;

		} else {
			$this->type = self::RELATIVE;
		}

		return $this->mask;
	}


	private function normalizeMetadata(array $metadata): array
	{
		foreach ($metadata as $name => $meta) {
			if (!is_array($meta)) {
				$metadata[$name] = $meta = [self::VALUE => $meta];
			}

			if (array_key_exists(self::VALUE, $meta)) {
				if (is_scalar($meta[self::VALUE])) {
					$metadata[$name][self::VALUE] = $meta[self::VALUE] === false
						? '0'
						: (string) $meta[self::VALUE];
				}

				$metadata[$name]['fixity'] = self::CONSTANT;
			}
		}

		return $metadata;
	}


	private function parseMask(string $path): void
	{
		// <parameter-name[=default] [pattern]> or [ or ] or ?...
		$parts = Strings::split($path, '/<([^<>= ]+)(=[^<> ]*)? *([^<>]*)>|(\[!?|\]|\s*\?.*)/');

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

			if ($pattern === '' && isset($meta[self::PATTERN])) {
				$pattern = $meta[self::PATTERN];
			}

			if ($default !== '') {
				$meta[self::VALUE] = substr($default, 1);
				$meta[self::FIXITY] = self::IN_PATH;
			}

			$meta[self::FILTER_TABLE_OUT] = empty($meta[self::FILTER_TABLE])
				? null
				: array_flip($meta[self::FILTER_TABLE]);
			if (array_key_exists(self::VALUE, $meta)) {
				if (isset($meta[self::FILTER_TABLE_OUT][$meta[self::VALUE]])) {
					$meta[self::DEFAULT] = $meta[self::FILTER_TABLE_OUT][$meta[self::VALUE]];

				} elseif (isset($meta[self::VALUE], $meta[self::FILTER_OUT])) {
					$meta[self::DEFAULT] = $meta[self::FILTER_OUT]($meta[self::VALUE]);

				} else {
					$meta[self::DEFAULT] = $meta[self::VALUE];
				}
			}

			$meta[self::PATTERN] = $pattern;

			// include in expression
			$this->aliases['p' . $i] = $name;
			$re = '(?P<p' . $i . '>(?U)' . $pattern . ')' . $re;
			if ($brackets) { // is in brackets?
				if (!isset($meta[self::VALUE])) {
					$meta[self::VALUE] = $meta[self::DEFAULT] = null;
				}

				$meta[self::FIXITY] = self::IN_PATH;

			} elseif (isset($meta[self::FIXITY])) {
				if ($autoOptional) {
					$re = '(?:' . $re . ')?';
				}

				$meta[self::FIXITY] = self::IN_PATH;

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


	private function parseQuery(array $parts): bool
	{
		$query = $parts[count($parts) - 2] ?? '';
		if (substr(ltrim($query), 0, 1) !== '?') {
			return false;
		}

		// name=<parameter-name [pattern]>
		$matches = Strings::matchAll($query, '/(?:([a-zA-Z0-9_.-]+)=)?<([^> ]+) *([^>]*)>/');

		foreach ($matches as [, $param, $name, $pattern]) { // $pattern is not used
			$meta = ($this->metadata[$name] ?? []) + ($this->defaultMeta['?' . $name] ?? []);

			if (array_key_exists(self::VALUE, $meta)) {
				$meta[self::FIXITY] = self::IN_QUERY;
			}

			unset($meta[self::PATTERN]);
			$meta[self::FILTER_TABLE_OUT] = empty($meta[self::FILTER_TABLE])
				? null
				: array_flip($meta[self::FILTER_TABLE]);

			$this->metadata[$name] = $meta;
			if ($param !== '') {
				$this->xlat[$name] = $param;
			}
		}

		return true;
	}


	/********************* Utilities ****************d*g**/


	/**
	 * Rename keys in array.
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
	 * Url encode.
	 */
	public static function param2path(string $s): string
	{
		return str_replace('%2F', '/', rawurlencode($s));
	}
}
