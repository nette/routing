<?php

/**
 * Test: Nette\Routing\Route with FILTER_IN & FILTER_OUT
 */

declare(strict_types=1);

use Nette\Routing\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<presenter>', [
	null => [
		Route::FilterIn => function (array $arr) {
			if (substr($arr['presenter'], 0, 3) !== 'abc') {
				return null;
			}

			$arr['presenter'] .= '.in';
			$arr['param'] .= '.in';
			return $arr;
		},
		Route::FilterOut => function (array $arr) {
			if (substr($arr['presenter'], 0, 3) !== 'abc') {
				return null;
			}

			$arr['presenter'] .= '.out';
			$arr['param'] .= '.out';
			return $arr;
		},
	],
]);

testRouteIn($route, '/abc?param=1', [
	'presenter' => 'abc.in',
	'param' => '1.in',
	'test' => 'testvalue',
], '/abc.in.out?param=1.in.out&test=testvalue');

testRouteIn($route, '/cde?param=1');

Assert::null(testRouteOut($route, ['presenter' => 'cde']));


$route = new Route('<lang>/<presenter>/<action>', [
	null => [
		Route::FilterIn => function (array $arr) {
			if ($arr['presenter'] !== 'abc-cs') {
				return null;
			}

			$arr['presenter'] = substr($arr['presenter'], 0, -3);
			$arr['action'] = substr($arr['action'], 0, -3);
			return $arr;
		},
		Route::FilterOut => function (array $arr) {
			if ($arr['presenter'] !== 'abc') {
				return null;
			}

			$arr['presenter'] .= '-' . $arr['lang'];
			$arr['action'] .= '-' . $arr['lang'];
			return $arr;
		},
	],
]);


testRouteIn($route, '/cs/abc-cs/def-cs', [
	'presenter' => 'abc',
	'lang' => 'cs',
	'action' => 'def',
	'test' => 'testvalue',
], '/cs/abc-cs/def-cs?test=testvalue');

Assert::same(
	'http://example.com/cs/abc-cs/def-cs?test=testvalue',
	testRouteOut($route, ['presenter' => 'abc', 'lang' => 'cs', 'action' => 'def', 'test' => 'testvalue'])
);
