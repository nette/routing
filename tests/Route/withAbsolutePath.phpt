<?php

/**
 * Test: Nette\Routing\Route with WithAbsolutePath
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('/<abspath>/');

testRouteIn($route, '/abc', [
	'abspath' => 'abc',
	'test' => 'testvalue',
], '/abc/?test=testvalue');
