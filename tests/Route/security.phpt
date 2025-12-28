<?php

/**
 * Test: Nette\Routing\Route security edge cases
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('path traversal patterns in parameter values fail URL generation', function () {
	$route = new Route('<presenter>/<action>/<file>');

	// Path traversal patterns cause URL generation to fail (security feature)
	Assert::null(testRouteOut($route, ['presenter' => 'files', 'action' => 'read', 'file' => '../../etc/passwd']));
	Assert::null(testRouteOut($route, ['presenter' => 'files', 'action' => 'read', 'file' => '../secret']));

	// But normal filenames work
	Assert::same(
		'http://example.com/files/read/document.pdf',
		testRouteOut($route, ['presenter' => 'files', 'action' => 'read', 'file' => 'document.pdf']),
	);
});


test('null bytes are handled safely', function () {
	$route = new Route('<presenter>/<file>');

	// Null byte in parameter (should be encoded)
	$params = ['presenter' => 'test', 'file' => "file\x00.txt"];
	$url = testRouteOut($route, $params);

	Assert::notNull($url);
	Assert::contains('%00', $url);
});


test('extremely long parameter values', function () {
	$route = new Route('<presenter>/<param>');

	// Very long parameter (10000 characters)
	$longValue = str_repeat('a', 10000);
	$url = testRouteOut($route, ['presenter' => 'test', 'param' => $longValue]);

	Assert::notNull($url);
	Assert::contains($longValue, $url);
});


test('UTF-8 multibyte characters are handled correctly', function () {
	$route = new Route('<presenter>/<title>');

	// Czech characters
	testRouteIn($route, '/blog/příliš-žluťoučký-kůň', [
		'presenter' => 'blog',
		'title' => 'příliš-žluťoučký-kůň',
		'test' => 'testvalue',
	], '/blog/p%C5%99%C3%ADli%C5%A1-%C5%BElu%C5%A5ou%C4%8Dk%C3%BD-k%C5%AF%C5%88?test=testvalue');

	// Emoji
	$url = testRouteOut($route, ['presenter' => 'emoji', 'title' => '🚀-rocket']);
	Assert::contains('%F0%9F%9A%80', $url);

	// Chinese characters
	$url = testRouteOut($route, ['presenter' => 'test', 'title' => '你好世界']);
	Assert::notNull($url);
});


test('special characters are URL-encoded properly', function () {
	$route = new Route('<presenter>/<search>');

	// Route generates valid URLs - XSS protection is application layer responsibility
	$url = testRouteOut($route, ['presenter' => 'search', 'search' => 'hello world']);
	Assert::same('http://example.com/search/hello%20world', $url);

	// Angle brackets
	$url = testRouteOut($route, ['presenter' => 'search', 'search' => 'a<b']);
	if ($url !== null) {
		Assert::contains('%3C', $url); // < encoded
	}

	// Safe alphanumeric and dash/underscore work
	$url = testRouteOut($route, ['presenter' => 'search', 'search' => 'hello-world_123']);
	Assert::notNull($url);
});


test('query parameter pollution - extra params are preserved', function () {
	$route = new Route('<presenter> ? page=<page>');

	// Additional query parameters are captured
	testRouteIn($route, '/list?page=1&admin=true', [
		'presenter' => 'list',
		'page' => '1',
		'admin' => 'true',
		'test' => 'testvalue',
	], '/list?page=1&test=testvalue&admin=true');
});


test('CRLF injection in parameters', function () {
	$route = new Route('<presenter>/<param>');

	// CRLF characters should be encoded
	$url = testRouteOut($route, ['presenter' => 'test', 'param' => "line1\r\nline2"]);
	Assert::notNull($url);
	Assert::contains('%0D%0A', $url);

	// Tab characters
	$url = testRouteOut($route, ['presenter' => 'test', 'param' => "val1\tval2"]);
	Assert::contains('%09', $url);
});


test('percent-encoded values are decoded in parameters', function () {
	$route = new Route('<presenter>/<param>');

	// Space encoded as %20 is decoded
	testRouteIn($route, '/test/hello%20world', [
		'presenter' => 'test',
		'param' => 'hello world',
		'test' => 'testvalue',
	], '/test/hello%20world?test=testvalue');

	// Plus sign - stays as +
	testRouteIn($route, '/test/a%2Bb', [
		'presenter' => 'test',
		'param' => 'a+b',
		'test' => 'testvalue',
	], '/test/a+b?test=testvalue');
});


test('unicode normalization edge cases', function () {
	$route = new Route('<presenter>/<name>');

	// Combining characters
	$url = testRouteOut($route, ['presenter' => 'test', 'name' => 'café']); // e with combining acute
	Assert::notNull($url);

	// Zero-width characters
	$url = testRouteOut($route, ['presenter' => 'test', 'name' => "test\u{200B}value"]); // zero-width space
	Assert::notNull($url);
});


test('parameter with only whitespace', function () {
	$route = new Route('<presenter>/<param>');

	// Space
	$url = testRouteOut($route, ['presenter' => 'test', 'param' => ' ']);
	Assert::same('http://example.com/test/%20', $url);

	// Tab
	$url = testRouteOut($route, ['presenter' => 'test', 'param' => "\t"]);
	Assert::contains('%09', $url);

	// Newline
	$url = testRouteOut($route, ['presenter' => 'test', 'param' => "\n"]);
	Assert::contains('%0A', $url);
});


test('backslash in parameters (Windows path separators)', function () {
	$route = new Route('<presenter>/<path>');

	// Backslashes should be encoded
	$url = testRouteOut($route, ['presenter' => 'files', 'path' => 'folder\file.txt']);
	Assert::contains('%5C', $url);

	testRouteIn($route, '/files/folder%5Cfile.txt', [
		'presenter' => 'files',
		'path' => 'folder\file.txt',
		'test' => 'testvalue',
	], '/files/folder%5Cfile.txt?test=testvalue');
});


test('URL with percent signs in parameter values', function () {
	$route = new Route('<presenter>/<discount>');

	// Percent sign in value
	$url = testRouteOut($route, ['presenter' => 'shop', 'discount' => '20%']);
	Assert::contains('20%25', $url);

	testRouteIn($route, '/shop/20%25', [
		'presenter' => 'shop',
		'discount' => '20%',
		'test' => 'testvalue',
	], '/shop/20%25?test=testvalue');
});


test('internationalized domain names (IDN)', function () {
	$route = new Route('//%host%/<path>');

	// Czech domain
	$url = new Nette\Http\UrlScript('http://příklad.cz/test');
	$httpRequest = new Nette\Http\Request($url);

	$params = $route->match($httpRequest);
	Assert::notNull($params);
	Assert::same('test', $params['path']);
});


test('control characters in parameters are encoded', function () {
	$route = new Route('<presenter>/<param>');

	// Test a few control characters
	$testChars = [0 => '%00', 9 => '%09', 10 => '%0A', 13 => '%0D'];

	foreach ($testChars as $ord => $encoded) {
		$char = chr($ord);
		$url = testRouteOut($route, ['presenter' => 'test', 'param' => "val{$char}ue"]);
		if ($url !== null) {
			Assert::contains($encoded, strtoupper($url));
		}
	}
});
