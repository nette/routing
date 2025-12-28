<?php

/**
 * Test: Nette\Routing\Route public API methods
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('getMask() returns original mask string', function () {
	$route = new Route('<presenter>/<action>/<id>');
	Assert::same('<presenter>/<action>/<id>', $route->getMask());

	$route = new Route('//example.com/<path>');
	Assert::same('//example.com/<path>', $route->getMask());

	$route = new Route('index[.html]');
	Assert::same('index[.html]', $route->getMask());
});


test('getMask() with complex patterns', function () {
	$mask = '<presenter>/<action>[/<id \d+>][/<slug [-a-z]+>]';
	$route = new Route($mask);
	Assert::same($mask, $route->getMask());
});


test('getMask() with query parameters', function () {
	$mask = '<presenter> ? id=<id> & cat=<categoryId>';
	$route = new Route($mask);
	Assert::same($mask, $route->getMask());
});


test('getMask() with host patterns', function () {
	$mask = '//%domain%/<presenter>/<action>';
	$route = new Route($mask);
	Assert::same($mask, $route->getMask());
});


test('getDefaults() returns all parameters with default values', function () {
	$route = new Route('<presenter>/<action>', [
		'action' => 'default',
		'id' => null,
	]);

	Assert::same(['action' => 'default', 'id' => null], $route->getDefaults());
});


test('getDefaults() with various default types - converts to strings', function () {
	$route = new Route('<presenter>/<action>/<page>', [
		'action' => 'view',
		'page' => 1,
		'lang' => 'en',
		'debug' => false,
	]);

	Assert::same([
		'action' => 'view',
		'page' => '1',
		'lang' => 'en',
		'debug' => '0',
	], $route->getDefaults());
});


test('getDefaults() excludes parameters without defaults', function () {
	$route = new Route('<presenter>/<action>', [
		'action' => 'default',
	]);

	$defaults = $route->getDefaults();
	Assert::same(['action' => 'default'], $defaults);
	Assert::false(array_key_exists('presenter', $defaults));
});


test('getConstantParameters() returns only fixed constant values', function () {
	$route = new Route('api/<version>', [
		'module' => 'Api',
		'version' => 'v1',
	]);

	Assert::same(['module' => 'Api'], $route->getConstantParameters());
});


test('getConstantParameters() with multiple constants', function () {
	$route = new Route('blog/<slug>', [
		'presenter' => 'Blog',
		'action' => 'detail',
		'module' => 'Front',
	]);

	Assert::same([
		'presenter' => 'Blog',
		'action' => 'detail',
		'module' => 'Front',
	], $route->getConstantParameters());
});


test('getConstantParameters() excludes variable parameters', function () {
	$route = new Route('<presenter>/<action=default>/<id>', [
		'action' => 'default',
		'module' => 'Front',
	]);

	$constants = $route->getConstantParameters();
	Assert::same(['module' => 'Front'], $constants);
	Assert::false(array_key_exists('action', $constants));
	Assert::false(array_key_exists('presenter', $constants));
	Assert::false(array_key_exists('id', $constants));
});


test('getConstantParameters() with empty result', function () {
	$route = new Route('<presenter>/<action>', [
		'action' => 'default',
	]);

	Assert::same([], $route->getConstantParameters());
});


test('API methods work together consistently', function () {
	$route = new Route('<presenter>/<action=default>/<id>', [
		'action' => 'default',
		'module' => 'Admin',
		'secure' => true,
	]);

	Assert::same('<presenter>/<action=default>/<id>', $route->getMask());

	Assert::same([
		'action' => 'default',
		'module' => 'Admin',
		'secure' => '1',
	], $route->getDefaults());

	Assert::same([
		'module' => 'Admin',
		'secure' => '1',
	], $route->getConstantParameters());
});
