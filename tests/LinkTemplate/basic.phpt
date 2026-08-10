<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\LinkTemplate
 */

use Nette\Routing\LinkTemplate;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


enum TestSuit: string
{
	case Hearts = 'hearts';
}


function gridTemplate(): LinkTemplate
{
	return LinkTemplate::fromParts([
		'/product/',
		LinkTemplate::pathVariable('id'),
		['name' => null, 'key' => 'do=grid-sort', 'encode' => null],
		LinkTemplate::queryVariable('item_id', 'grid-item_id'),
		LinkTemplate::queryVariable('prev_id', 'grid-prev_id'),
		'#frag',
	]);
}


test('toTemplateString() exports variables as {name}', function () {
	Assert::same(
		'/product/{id}?do=grid-sort&grid-item_id={item_id}&grid-prev_id={prev_id}#frag',
		gridTemplate()->toTemplateString(),
	);
});


test('expand inserts values and keeps the fragment', function () {
	Assert::same(
		'/product/12?do=grid-sort&grid-item_id=5&grid-prev_id=7#frag',
		gridTemplate()->expand(['id' => 12, 'item_id' => 5, 'prev_id' => 7]),
	);
});


test('an unexpanded query variable disappears together with its pair', function () {
	Assert::same(
		'/product/12?do=grid-sort&grid-item_id=5#frag',
		gridTemplate()->expand(['id' => 12, 'item_id' => 5]),
	);
});


test('an unexpanded path variable is an error', function () {
	$template = LinkTemplate::fromParts(['/product/', LinkTemplate::pathVariable('id')]);
	Assert::exception(
		fn() => $template->expand([]),
		Nette\InvalidArgumentException::class,
		"Missing value for path variable 'id'.",
	);
});


test('an unknown variable is an error, catching typos', function () {
	$template = LinkTemplate::fromParts(['/product/', LinkTemplate::pathVariable('id')]);
	Assert::exception(
		fn() => $template->expand(['Id' => 1]),
		Nette\InvalidArgumentException::class,
		"Unknown variable 'Id', template only has id.",
	);
});


test('path keeps slashes intact, query encodes them', function () {
	$template = LinkTemplate::fromParts([
		'/file/',
		LinkTemplate::pathVariable('path'),
		LinkTemplate::queryVariable('q', 'q'),
	]);
	Assert::same('/file/a/b%20c?q=a%2Fb+c', $template->expand(['path' => 'a/b c', 'q' => 'a/b c']));
});


test('bool and backed enum values are converted like the router does', function () {
	$template = LinkTemplate::fromParts([
		'/file/',
		LinkTemplate::pathVariable('path'),
		LinkTemplate::queryVariable('q', 'q'),
	]);
	Assert::same('/file/0?q=hearts', $template->expand(['path' => false, 'q' => TestSuit::Hearts]));
});


test('a leading unexpanded query pair drops the question mark too', function () {
	$template = LinkTemplate::fromParts(['/list', LinkTemplate::queryVariable('page', 'page')]);
	Assert::same('/list', $template->expand([]));
	Assert::same('/list?page=2', $template->expand(['page' => 2]));
});


test('when the first pair is unexpanded, the question mark passes to the next one', function () {
	$template = LinkTemplate::fromParts([
		'/list',
		LinkTemplate::queryVariable('page', 'page'),
		['name' => null, 'key' => 'sort=name', 'encode' => null],
	]);
	Assert::same('/list?sort=name', $template->expand([]));
	Assert::same('/list?page=2&sort=name', $template->expand(['page' => 2]));
});


test('an array value in a query variable expands to multiple pairs', function () {
	$template = LinkTemplate::fromParts(['/list', LinkTemplate::queryVariable('ids', 'ids')]);
	Assert::same('/list?ids%5B0%5D=1&ids%5B1%5D=2', $template->expand(['ids' => [1, 2]]));
	Assert::same('/list?ids%5Ba%5D=x', $template->expand(['ids' => ['a' => 'x']]));
	Assert::same('/list', $template->expand(['ids' => []]));
});


test('an array value in a path variable is an error', function () {
	$template = LinkTemplate::fromParts(['/product/', LinkTemplate::pathVariable('id')]);
	Assert::exception(
		fn() => $template->expand(['id' => [1, 2]]),
		Nette\InvalidArgumentException::class,
		'Value must be scalar or backed enum, array given.',
	);
});


test('a variable encoder produces the final URL form of the value', function () {
	$template = LinkTemplate::fromParts([
		'/report/',
		LinkTemplate::pathVariable('date', fn(string $s) => date('Y-m-d', (int) $s)),
		LinkTemplate::queryVariable('mode', 'mode', strtoupper(...)),
	]);
	Assert::same('/report/2023-11-14?mode=FULL', $template->expand(['date' => '1700000000', 'mode' => 'full']));
});


test('a custom separator joins query pairs and survives export', function () {
	$template = LinkTemplate::fromParts(
		[
			'/list',
			['name' => null, 'key' => 'a=1', 'encode' => null],
			LinkTemplate::queryVariable('page', 'page'),
		],
		';',
	);
	Assert::same('/list?a=1;page={page}', $template->toTemplateString());
	Assert::same('/list?a=1;page=2', $template->expand(['page' => 2]));
});


test('toParts() decomposes the template so a caller can adjust and reassemble it', function () {
	[$parts, $separator] = gridTemplate()->toParts();
	Assert::same(
		gridTemplate()->toTemplateString(),
		LinkTemplate::fromParts($parts, $separator)->toTemplateString(),
	);

	$parts[1]['name'] = 'product_id'; // rename a variable, like LinkGenerator does
	$template = LinkTemplate::fromParts($parts, $separator);
	Assert::same(
		'/product/{product_id}?do=grid-sort&grid-item_id={item_id}&grid-prev_id={prev_id}#frag',
		$template->toTemplateString(),
	);
	Assert::exception(
		fn() => $template->expand(['id' => 5]),
		Nette\InvalidArgumentException::class,
		"Unknown variable 'id', template only has product_id, item_id, prev_id.",
	);
});
