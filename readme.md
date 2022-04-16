Nette Routing: two-ways URL conversion
**************************************

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/routing.svg)](https://packagist.org/packages/nette/routing)
[![Tests](https://github.com/nette/routing/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/routing/actions)
[![Coverage Status](https://coveralls.io/repos/github/nette/routing/badge.svg?branch=master)](https://coveralls.io/github/nette/routing?branch=master)

Introduction
============

The router is responsible for everything about URLs so that you no longer have to think about them. We will show:

- how to set up the router so that the URLs look like you want
- a few notes about SEO redirection
- and we'll show you how to write your own router

It requires PHP version 7.1 and supports PHP up to 8.0.

Documentation can be found on the [website](https://doc.nette.org/routing).


[Support Me](https://github.com/sponsors/dg)
--------------------------------------------

Do you like Nette Routing? Are you looking forward to the new features?

[![Buy me a coffee](https://files.nette.org/icons/donation-3.svg)](https://github.com/sponsors/dg)

Thank you!


Basics
======

More human URLs (or cool or pretty URLs) are more usable, more memorable, and contribute positively to SEO. Nette Framework keeps this in mind and fully meets developers' desires.

Let's start a little technically. A router is an object that implements the [Nette\Routing\Router](https://api.nette.org/routing/Nette/Routing/Router.html) interface, which can decompose a URL into an array of parameters (method `match`) and, conversely, build a URL from an array of parameters (method `constructUrl`). Therefore, it is also said that the router is bidirectional.
Nette brings a very elegant way to define  how the URLs of your application look like.

You can use it in the same way in completely different cases, for the REST API, for applications where controllers are not used at all, etc.

Thus, routing is a separate and sophisticated layer of the application, thanks to which the look of URL addresses can be easily designed or changed when the entire application is ready, because it can be done without modification of the code or templates. Which gives developers huge freedom.


Route Collection
================

The most pleasant way to define the URL addresses in the application is via the class [Nette\Routing\RouteList](https://api.nette.org/routing/Nette/Routing/RouteList.html). The big advantage is that the whole router
is defined in one place and is not so scattered in the form of annotations in all controllers.

The definition consists of a list of so-called routes, ie masks of URL addresses and their associated controllers and actions using a simple API. We do not have to name the routes.

```php
$router = new Nette\Routing\RouteList;
$router->addRoute('rss.xml', [
	'controller' => 'RssFeedController',
]);
$router->addRoute('article/<id \d+>', [
	'controller' => 'ArticleController',
]);
...
```

Order of routes is important because they are tried sequentially from the first one to the last one. Basic rule is to **declare routes from the most specific to the most general**.

Now we have to let the router to work:

```php
$params = $router->match($httpRequest);
if ($params === null) {
	// no matching route found, we will send a 404 error
	exit;
}

// we process the received parameters
$controller = $params['controller'];
...
```

And vice versa, we will use the router to create the link:

```php
$params = ['controller' => 'ArticleController', 'id' => 123];
$url = $router->constructUrl($params, $httpRequest->getUrl());
```


Mask and Parameters
-------------------

The mask describes the relative path based on the site root. The simplest mask is a static URL:

```php
$router->addRoute('products', ...);
```

Often masks contain so-called **parameters**. They are enclosed in angle brackets (e.g. `<year>`).

```php
$router->addRoute('chronicle/<year>', ...);
```

We can specify a default value for the parameters directly in the mask and thus it becomes optional:

```php
$router->addRoute('chronicle/<year=2020>', ...);
```

The route will now accept the URL `https://any-domain.com/chronicle/`, which will again display with parameter `year: 2020`.

The mask can describe not only the relative path based on the site root, but also the absolute path when it begins with a slash, or even the entire absolute URL when it begins with two slashes:

```php
// relative path to application document root
$router->addRoute('<controller>/<action>', ...);

// absolute path, relative to server hostname
$router->addRoute('/<controller>/<action>', ...);

// absolute URL including hostname (but scheme-relative)
$router->addRoute('//<lang>.example.com/<controller>/<action>', ...);

// absolute URL including schema
$router->addRoute('https://<lang>.example.com/<controller>/<action>', ...);
```


Validation Expressions
----------------------

A validation condition can be specified for each parameter using [regular expression ](https://www.php.net/manual/en/reference.pcre.pattern.syntax.php). For example, let's set `id` to be only numerical, using `\d+` regexp:

```php
$router->addRoute('<controller>/<action>[/<id \d+>]', ...);
```

The default regular expression for all parameters is `[^/]+`, ie everything except the slash. If a parameter is supposed to match a slash as well, we set the regular expression to `.+`.

```php
// accepts https://example.com/a/b/c, path is 'a/b/c'
$router->addRoute('<path .+>', ...);
```


Optional Sequences
------------------

Square brackets denote optional parts of mask. Any part of mask may be set as optional, including those containing parameters:

```php
$router->addRoute('[<lang [a-z]{2}>/]<name>', ...);

// Accepted URLs:      Parameters:
//   /en/download        lang => en, name => download
//   /download           lang => null, name => download
```

Of course, when a parameter is part of an optional sequence, it also becomes optional. If it does not have a default value, it will be null.

Optional sections can also be in the domain:

```php
$router->addRoute('//[<lang=en>.]example.com/<controller>/<action>', ...);
```

Sequences may be freely nested and combined:

```php
$router->addRoute(
	'[<lang [a-z]{2}>[-<sublang>]/]<name>[/page-<page=0>]',
	...
);

// Accepted URLs:
//   /cs/hello
//   /en-us/hello
//   /hello
//   /hello/page-12
```

URL generator tries to keep the URL as short as possible, so what can be omitted is omitted. Therefore, for example, a route `index[.html]` generates a path `/index`. You can reverse this behavior by writing an exclamation mark after the left square bracket:

```php
// accepts both /hello and /hello.html, generates /hello
$router->addRoute('<name>[.html]', ...);

// accepts both /hello and /hello.html, generates /hello.html
$router->addRoute('<name>[!.html]', ...);
```

Optional parameters (ie. parameters having default value) without square brackets do behave as if wrapped like this:

```php
$router->addRoute('<controller=Homepage>/<action=default>/<id=>', ...);

// equals to:
$router->addRoute('[<controller=Homepage>/[<action=default>/[<id>]]]', ...);
```

To change how the rightmost slash is generated, i.e. instead of `/homepage/` get a `/homepage`, adjust the route this way:

```php
$router->addRoute('[<controller=Homepage>[/<action=default>[/<id>]]]', ...);
```


Wildcards
---------

In the absolute path mask, we can use the following wildcards to avoid, for example, the need to write a domain to the mask, which may differ in the development and production environment:

- `%tld%` = top level domain, e.g. `com` or `org`
- `%sld%` = second level domain, e.g. `example`
- `%domain%` = domain without subdomains, e.g. `example.com`
- `%host%` = whole host, e.g. `www.example.com`
- `%basePath%` = path to the root directory

```php
$router->addRoute('//www.%domain%/%basePath%/<controller>/<action>', ...);
$router->addRoute('//www.%sld%.%tld%/%basePath%/<controller>/<action', ...);
```


Second Parameter
----------------

The second parameter of the route is array of default values ​​of individual parameters:

```php
$router->addRoute('<controller>/<action>[/<id \d+>]', [
	'controller' => 'Homepage',
	'action' => 'default',
]);
```

Or we can use this form, notice the rewriting of the validation regular expression:

```php
use Nette\Routing\Route;

$router->addRoute('<controller>/<action>[/<id>]', [
	'controller' => [
		Route::Value => 'Homepage',
	],
	'action' => [
		Route::Value => 'default',
	],
	'id' => [
		Route::Pattern => '\d+',
	],
]);
```

These more talkative formats are useful for adding other metadata.


Filters and Translations
------------------------

It's a good practice to write source code in English, but what if you need your website to have translated URL to different language? Simple routes such as:

```php
$router->addRoute('<controller>/<action>', [...]);
```

will generate English URLs, such as `/product/123` or `/cart`. If we want to have controllers and actions in the URL translated to Deutsch (e.g. `/produkt/123` or `/einkaufswagen`), we can use a translation dictionary. To add it, we already need a "more talkative" variant of the second parameter:

```php
use Nette\Routing\Route;

$router->addRoute('<controller>/<action>', [
	'controller' => [
		Route::Value => 'Homepage',
		Route::FilterTable => [
			// string in URL => controller
			'produkt' => 'Product',
			'einkaufswagen' => 'Cart',
			'katalog' => 'Catalog',
		],
	],
	'action' => [
		Route::Value => 'default',
		Route::FilterTable => [
			'liste' => 'list',
		],
	],
]);
```

Multiple dictionary keys can by used for the same controller. They will create various aliases for it. The last key is considered to be the canonical variant (i.e. the one that will be in the generated URL).

The translation table can be applied to any parameter in this way. However, if the translation does not exist, the original value is taken. We can change this behavior by adding `Router::FILTER_STRICT => true` and the route will then reject the URL if the value is not in the dictionary.

In addition to the translation dictionary in the form of an array, it is possible to set own translation functions:

```php
use Nette\Routing\Route;

$router->addRoute('<controller>/<action>/<id>', [
	'controller' => [
		Route::Value => 'Homepage',
		Route::FilterIn => function (string $s): string { ... },
		Route::FilterOut => function (string $s): string { ... },
	],
	'action' => 'default',
	'id' => null,
]);
```

The function `Route::FILTER_IN` converts between the parameter in the URL and the string, which is then passed to the controller, the function `FILTER_OUT` ensures the conversion in the opposite direction.


Global Filters
--------------

Besides filters for specific parameters, you can also define global filters that receive an associative array of all parameters that they can modify in any way and then return. Global filters are defined under `null` key.

```php
use Nette\Routing\Route;

$router->addRoute('<controller>/<action>', [
	'controller' => 'Homepage',
	'action' => 'default',
	null => [
		Route::FilterIn => function (array $params): array { ... },
		Route::FilterOut => function (array $params): array { ... },
	],
]);
```

Global filters give you the ability to adjust the behavior of the route in absolutely any way. We can use them, for example, to modify parameters based on other parameters. For example, translation `<controller>` and `<action>` based on the current value of parameter `<lang>`.

If a parameter has a custom filter defined and a global filter exists at the same time, custom `FILTER_IN` is executed before the global and vice versa global `FILTER_OUT` is executed before the custom. Thus, inside the global filter are the values of the parameters `controller` resp. `action` written in PascalCase resp. camelCase style.


ONE_WAY flag
------------

One-way routes are used to preserve the functionality of old URLs that the application no longer generates but still accepts. We flag them with `ONE_WAY`:

```php
// old URL /product-info?id=123
$router->addRoute('product-info', [...], $router::ONE_WAY);
// new URL /product/123
$router->addRoute('product/<id>', [...]);
```

When accessing the old URL, the controller automatically redirects to the new URL so that search engines do not index these pages twice (see [#SEO and canonization]).


Subdomains
----------

Route collections can be grouped by subdomains:

```php
$router = new RouteList;
$router->withDomain('example.com')
	->addRoute('rss', [...])
	->addRoute('<controller>/<action>');
```

You can also use [#wildcards] in your domain name:

```php
$router = new RouteList;
$router->withDomain('example.%tld%')
	...
```


Path Prefix
-----------

Route collections can be grouped by path in URL:

```php
$router = new RouteList;
$router->withPath('/eshop')
	->addRoute('rss', [...]) // matches URL /eshop/rss
	->addRoute('<controller>/<action>'); // matches URL /eshop/<controller>/<action>
```


Combinations
------------

The above usage can be combined:

```php
$router = (new RouteList)
	->withDomain('admin.example.com')
		->addRoute(...)
		->addRoute(...)
	->end()
	->withDomain('example.com')
		->withPath('export')
			->addRoute(...)
			...
```


Query Parameters
----------------

Masks can also contain query parameters (parameters after the question mark in the URL). They cannot define a validation expression, but they can change the name under which they are passed to the controller:

```php
// use query parameter 'cat' as a 'categoryId' in application
$router->addRoute('product ? id=<productId> & cat=<categoryId>', ...);
```


Foo Parameters
--------------

We're going deeper now. Foo parameters are basically unnamed parameters which allow to match a regular expression. The following route matches `/index`, `/index.html`, `/index.htm` and `/index.php`:

```php
$router->addRoute('index<? \.html?|\.php|>', ...);
```

It's also possible to explicitly define a string which will be used for URL generation. The string must be placed directly after the question mark. The following route is similar to the previous one, but generates `/index.html` instead of `/index` because the string `.html` is set as a "generated value".

```php
$router->addRoute('index<?.html \.html?|\.php|>', ...);
```


SimpleRouter
============

A much simpler router than the Route Collection is [SimpleRouter](https://api.nette.org/routing/Nette/Routing/SimpleRouter.html). It can be used when there's no need for a specific URL format, when `mod_rewrite` (or alternatives) is not available or when we simply do not want to bother with user-friendly URLs yet.

Generates addresses in roughly this form:

```
http://example.com/?controller=Product&action=detail&id=123
```

The parameter of the `SimpleRouter` constructor is a default controller & action, ie. action to be executed if we open e.g. `http://example.com/` without additional parameters.

```php
$router = new Nette\Application\Routers\SimpleRouter();
```


SEO and Canonization
====================

The framework increases SEO (search engine optimization) by preventing duplication of content at different URLs. If multiple addresses link to a same destination, eg `/index` and `/index.html`, the framework determines the first one as primary (canonical) and redirects the others to it using HTTP code 301. Thanks to this, search engines will not index pages twice and do not break their page rank. .

This process is called canonization. The canonical URL is the one generated by the router, i.e. by the first matching route in the [collection ](#route-collection) without the ONE_WAY flag. Therefore, in the collection, we list **primary routes first**.

Canonization is performed by the controller, more in the chapter [canonization ](controllers#Canonization).


HTTPS
=====

In order to use the HTTPS protocol, it is necessary to activate it on hosting and to configure the server.

Redirection of the entire site to HTTPS must be performed at the server level, for example using the .htaccess file in the root directory of our application, with HTTP code 301. The settings may differ depending on the hosting and looks something like this:

```php
<IfModule mod_rewrite.c>
	RewriteEngine On
	...
	RewriteCond %{HTTPS} off
	RewriteRule .* https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
	...
</IfModule>
```

The router generates a URL with the same protocol as the page was loaded, so there is no need to set anything else.

However, if we exceptionally need different routes to run under different protocols, we will put it in the route mask:

```php
// Will generate an HTTP address
$router->addRoute('http://%host%/<controller>/<action>', ...);

// Will generate an HTTPS address
$router->addRoute('https://%host%/<controller>/<action>', ...);
```


Routing Debugger
================

We will not hide from you that routing may seem a bit magical at first, and before you get into it, Routing Debugger will be a good helper. This is a panel displayed in the [Tracy Bar ](tracy:), which provides a clear list of routes as well as parameters that the router obtained from the URL.

The green bar with symbol ✓ represents the route that matched the current URL, the blue bars with symbols ≈ indicate the routes that would also match the URL if green did not overtake them. We see the current controller & action further.


Custom Router
=============

The following lines are intended for very advanced users. You can create your own router and naturally add it into your route collection. The router is an implementation of the [Router](https://api.nette.org/routing/Nette/Routing/Router.html) interface with two methods:

```php
use Nette\Http\IRequest as HttpRequest;
use Nette\Http\UrlScript;

class MyRouter implements Nette\Routing\Router
{
	public function match(HttpRequest $httpRequest): ?array
	{
		// ...
	}

	public function constructUrl(array $params, UrlScript $refUrl): ?string
	{
		// ...
	}
}
```

Method `match` processes the current request in the parameter [$httpRequest ](http-request-response#HTTP request) (which offers more than just URL) into the an array containing the name of the controller and its parameters. If it cannot process the request, it returns null.

Method `constructUrl`, on the other hand, generates an absolute URL from the array of parameters. It can use the information from parameter `$refUrl`, which is the current URL.

To add custom router to the route collection, use `add()`:

```php
$router = new Nette\Application\Routers\RouteList;
$router->add(new MyRouter);
$router->addRoute(...);
...
```
