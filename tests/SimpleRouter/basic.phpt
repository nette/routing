<?php

/**
 * Test: Nette\Application\Routers\SimpleRouter basic functions.
 */

use Nette\Http;
use Nette\Application\Routers\SimpleRouter;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$router = new SimpleRouter([
	'id' => 12,
	'any' => 'anyvalue',
]);

$url = new Http\UrlScript('http://nette.org/file.php');
$url->setScriptPath('/file.php');
$url->setQuery([
	'presenter' => 'myPresenter',
	'action' => 'action',
	'id' => '12',
	'test' => 'testvalue',
]);
$httpRequest = new Http\Request($url);

$req = $router->match($httpRequest);
Assert::same('myPresenter',  $req->getPresenterName());
Assert::same('action',  $req->getParameters()['action']);
Assert::same('12',  $req->getParameters()['id']);
Assert::same('testvalue',  $req->getParameters()['test']);
Assert::same('anyvalue',  $req->getParameters()['any']);

$res = $router->constructUrl($req, $httpRequest->getUrl());
Assert::same('http://nette.org/file.php?action=action&test=testvalue&presenter=myPresenter', $res);


$url = new Http\UrlScript('https://nette.org/file.php');
$res = $router->constructUrl($req, $url);
Assert::same('https://nette.org/file.php?action=action&test=testvalue&presenter=myPresenter', $res);
