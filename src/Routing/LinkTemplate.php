<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Routing;

use Nette;
use function is_array, is_scalar, is_string, sprintf;


/**
 * URL template with {name} variables for deferred parameters; expand() encodes
 * values exactly as the router would.
 */
final class LinkTemplate
{
	/** @var list<string|array{name: ?string, key: ?string, encode: ?\Closure(string): string}> */
	private array $parts = [];

	private string $separator = '&';

	/** @var array<string, bool> variable name => is in path */
	private array $variables = [];


	private function __construct()
	{
	}


	/**
	 * Assembles a template from literals and parts produced by a router: a string is emitted
	 * verbatim, a part with a null key is a path variable, with a null name a fixed query
	 * pair carrying its rendered text in key, otherwise a query variable; query punctuation
	 * is added at expansion time.
	 * @param  list<string|array{name: ?string, key: ?string, encode: ?\Closure(string): string}>  $parts
	 * @internal
	 */
	public static function fromParts(array $parts, string $separator = '&'): self
	{
		$template = new self;
		$template->parts = $parts;
		$template->separator = $separator;
		foreach ($parts as $part) {
			if (!is_string($part) && $part['name'] !== null) {
				$template->variables[$part['name']] = $part['key'] === null;
			}
		}

		return $template;
	}


	/**
	 * Creates a path variable part; the encoder takes the application value and produces the final path form.
	 * @param  ?\Closure(string): string  $encode
	 * @return array{name: string, key: null, encode: \Closure(string): string}
	 * @internal
	 */
	public static function pathVariable(string $name, ?\Closure $encode = null): array
	{
		return ['name' => $name, 'key' => null, 'encode' => $encode ?? Route::param2path(...)];
	}


	/**
	 * Creates a query variable part; the handler output is url-encoded on top.
	 * @param  ?\Closure(string): string  $handler
	 * @return array{name: string, key: string, encode: \Closure(string): string}
	 * @internal
	 */
	public static function queryVariable(string $name, string $key, ?\Closure $handler = null): array
	{
		return [
			'name' => $name,
			'key' => urlencode($key),
			'encode' => $handler ? fn(string $value): string => urlencode($handler($value)) : urlencode(...),
		];
	}


	/**
	 * Creates a fixed query part for a key => value, rendered exactly like http_build_query()
	 * would; an array value yields its pairs joined with the separator in a single part.
	 * @return ?array{name: null, key: string, encode: null}
	 * @internal
	 */
	public static function queryPair(string|int $key, mixed $value, string $separator): ?array
	{
		$pairs = http_build_query(Route::objectsToArrays([$key => $value]), '', $separator);
		return $pairs === ''
			? null
			: ['name' => null, 'key' => $pairs, 'encode' => null];
	}


	/**
	 * Inserts values; an unexpanded query variable is omitted with its pair, an unexpanded path variable throws.
	 * A variable's encoder takes the application value and produces the URL form. An array value in a query
	 * variable expands to multiple pairs exactly like the router would; encoders apply to scalars only.
	 * @param  array<string, string|int|float|bool|\BackedEnum|array<mixed>|null>  $values
	 */
	public function expand(array $values): string
	{
		foreach (array_keys($values) as $name) {
			if (!isset($this->variables[$name])) {
				throw new Nette\InvalidArgumentException(sprintf(
					"Unknown variable '%s', template only has %s.",
					$name,
					implode(', ', array_keys($this->variables)) ?: 'none',
				));
			}
		}

		$url = '';
		$first = true;
		foreach ($this->parts as $part) {
			if (is_string($part)) {
				$url .= $part;

			} elseif ($part['key'] === null) { // path variable
				$name = $part['name'] ?? '';
				$value = $values[$name] ?? throw new Nette\InvalidArgumentException("Missing value for path variable '$name'.");

				$value = self::valueToString($value);
				$url .= $part['encode'] ? ($part['encode'])($value) : $value;

			} else { // query pair
				if ($part['name'] === null) { // fixed pair, rendered in full
					$text = $part['key'];
				} elseif (($value = $values[$part['name']] ?? null) === null) {
					continue; // unexpanded variable is omitted with its pair
				} elseif (is_array($value)) { // expands to multiple pairs like the router
					$text = http_build_query(Route::objectsToArrays([urldecode($part['key']) => $value]), '', $this->separator);
					if ($text === '') {
						continue;
					}
				} else {
					$value = self::valueToString($value);
					$text = $part['key'] . '=' . ($part['encode'] ? ($part['encode'])($value) : $value);
				}

				$url .= ($first ? '?' : $this->separator) . $text;
				$first = false;
			}
		}

		return $url;
	}


	private static function valueToString(mixed $value): string
	{
		return match (true) {
			$value instanceof \BackedEnum => (string) $value->value,
			$value === false => '0',
			is_scalar($value) => (string) $value,
			default => throw new Nette\InvalidArgumentException('Value must be scalar or backed enum, ' . get_debug_type($value) . ' given.'),
		};
	}


	/**
	 * Exports the template for the client with variables written as {name}.
	 */
	public function toTemplateString(): string
	{
		$url = '';
		$first = true;
		foreach ($this->parts as $part) {
			if (is_string($part)) {
				$url .= $part;

			} elseif ($part['key'] === null) { // path variable
				$url .= '{' . $part['name'] . '}';

			} else { // query pair
				$url .= ($first ? '?' : $this->separator)
					. ($part['name'] === null ? $part['key'] : $part['key'] . '={' . $part['name'] . '}');
				$first = false;
			}
		}

		return $url;
	}


	/**
	 * Counterpart of fromParts(): decomposes the template so the caller can adjust and reassemble it.
	 * @return array{list<string|array{name: ?string, key: ?string, encode: ?\Closure(string): string}>, string}
	 * @internal
	 */
	public function toParts(): array
	{
		return [$this->parts, $this->separator];
	}
}
