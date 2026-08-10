<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\LinkTemplate::expand() against the shared contract
 * cases also run by the JavaScript counterpart (tests/assets/expandLink.test.ts).
 */

use Nette\Routing\LinkTemplate;
use Nette\Utils\Json;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// test-only parser of the wire form; production code never reconstructs a template from a string
function parseTemplate(string $template): LinkTemplate
{
	$parts = [];
	$fragment = '';
	if (($pos = strpos($template, '#')) !== false) {
		$fragment = substr($template, $pos);
		$template = substr($template, 0, $pos);
	}

	$queryStr = null;
	if (($pos = strpos($template, '?')) !== false) {
		$queryStr = substr($template, $pos + 1);
		$template = substr($template, 0, $pos);
	}

	foreach (preg_split('#(\{[a-zA-Z_]\w*+\})#', $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $chunk) {
		$parts[] = preg_match('#^\{(\w+)\}$#', $chunk, $m)
			? LinkTemplate::pathVariable($m[1])
			: $chunk;
	}

	$separator = preg_match('#[&;]#', (string) $queryStr, $m) ? $m[0] : '&';
	foreach ($queryStr === null ? [] : preg_split('#[&;]#', $queryStr) as $pair) {
		$parts[] = preg_match('#^(.*)=\{([a-zA-Z_]\w*+)\}$#', $pair, $m)
			? LinkTemplate::queryVariable($m[2], $m[1])
			: ['name' => null, 'key' => $pair, 'encode' => null];
	}

	if ($fragment !== '') {
		$parts[] = $fragment;
	}

	return LinkTemplate::fromParts($parts, $separator);
}


$cases = Json::decode(file_get_contents(__DIR__ . '/../assets/expandLink.cases.json'), forceArrays: true);

foreach ($cases['expand'] as $case) {
	test($case['name'], function () use ($case) {
		$template = parseTemplate($case['template']);
		Assert::same($case['template'], $template->toTemplateString()); // the parser is faithful
		Assert::same($case['expected'], $template->expand($case['values']));
	});
}

foreach ($cases['errors'] as $case) {
	test($case['name'], function () use ($case) {
		Assert::exception(
			fn() => parseTemplate($case['template'])->expand($case['values']),
			Nette\InvalidArgumentException::class,
		);
	});
}
