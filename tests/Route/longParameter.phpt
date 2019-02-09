<?php

/**
 * Test: Nette\Routing\Route with LongParameter
 */

declare(strict_types=1);

use Nette\Routing\Route;


require __DIR__ . '/../bootstrap.php';


$route = new Route('<parameter-longer-than-32-characters>');

testRouteIn($route, '/any', [
	'parameter-longer-than-32-characters' => 'any',
	'test' => 'testvalue',
], '/any?test=testvalue');
