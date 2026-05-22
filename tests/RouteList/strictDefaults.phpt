<?php declare(strict_types=1);

/**
 * Test: Nette\Routing\RouteList::setStrictDefaults() rejects URLs with redundant default values.
 */

use Nette\Routing\RouteList;


require __DIR__ . '/../bootstrap.php';


test('strict defaults applied to the whole list', function () {
	$list = new RouteList;
	$list->setStrictDefaults();
	$list->addRoute('<presenter>/<action>', [
		'presenter' => 'home',
		'action' => 'default',
	]);

	// non-default values are accepted and round-trip
	testRouteIn($list, '/foo/bar', [
		'presenter' => 'foo',
		'action' => 'bar',
		'test' => 'testvalue',
	], '/foo/bar?test=testvalue');

	// trailing default is auto-omitted, so the short form is canonical
	testRouteIn($list, '/foo', [
		'presenter' => 'foo',
		'action' => 'default',
		'test' => 'testvalue',
	], '/foo/?test=testvalue');

	// a leading default followed by a non-default is canonical (cannot be omitted)
	testRouteIn($list, '/home/bar', [
		'presenter' => 'home',
		'action' => 'bar',
		'test' => 'testvalue',
	], '/home/bar?test=testvalue');

	// all defaults: the empty form is canonical
	testRouteIn($list, '/', [
		'presenter' => 'home',
		'action' => 'default',
		'test' => 'testvalue',
	], '/?test=testvalue');

	// redundant default values in the path are rejected
	testRouteIn($list, '/foo/default', null);
	testRouteIn($list, '/home/default', null);
	testRouteIn($list, '/home', null);
});


test('defaults inside an optional sequence', function () {
	$list = new RouteList;
	$list->setStrictDefaults();
	$list->addRoute('<presenter=home>[/<id=5>]');

	testRouteIn($list, '/foo/7', [
		'presenter' => 'foo',
		'id' => '7',
		'test' => 'testvalue',
	], '/foo/7?test=testvalue');

	testRouteIn($list, '/home/7', [
		'presenter' => 'home',
		'id' => '7',
		'test' => 'testvalue',
	], '/home/7?test=testvalue');

	testRouteIn($list, '/foo', [
		'presenter' => 'foo',
		'id' => '5',
		'test' => 'testvalue',
	], '/foo?test=testvalue');

	// id=5 equals its default and would be omitted -> redundant
	testRouteIn($list, '/foo/5', null);
	testRouteIn($list, '/home', null);
});


test('without strict defaults the redundant URLs still match', function () {
	$list = new RouteList;
	$list->addRoute('<presenter>/<action>', [
		'presenter' => 'home',
		'action' => 'default',
	]);

	testRouteIn($list, '/foo/default', [
		'presenter' => 'foo',
		'action' => 'default',
		'test' => 'testvalue',
	], '/foo/?test=testvalue');
});


test('setting propagates into nested withPath/withDomain lists', function () {
	$list = new RouteList;
	$list->setStrictDefaults();
	$list->withPath('eshop')
		->addRoute('<presenter>/<action>', [
			'presenter' => 'home',
			'action' => 'default',
		]);

	testRouteIn($list, '/eshop/foo/bar', [
		'presenter' => 'foo',
		'action' => 'bar',
		'test' => 'testvalue',
	], '/eshop/foo/bar?test=testvalue');

	testRouteIn($list, '/eshop/foo/default', null);
});
