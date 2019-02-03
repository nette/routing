<?php

/**
 * Test: Nette\Application\Routers\Route with WithAbsolutePath
 */

declare(strict_types=1);

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('/<abspath>/');

testRouteIn($route, '/abc', [
	'abspath' => 'abc',
	'test' => 'testvalue',
], '/abc/?test=testvalue');
