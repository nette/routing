# Upgrading nette/routing

## 3.1.4

- **Deprecated** `Nette\Routing\Router::ONE_WAY`: pass a non-zero `$oneWay` argument to `Nette\Routing\RouteList::add()`, `Nette\Routing\RouteList::addRoute()` or `Nette\Routing\RouteList::prepend()` instead; the old name is silently deprecated (db3bad68)
- **Changed** `Nette\Routing\Route::param2path()` now percent-encodes the double quote; previously it was left unencoded (since 3.1.1), so a generated URL path segment containing `"` differs (2f2c5f5d)
- **Changed** `Nette\Routing\RouteList::end()` now returns `null` when called on a list that was never created by `Nette\Routing\RouteList::withDomain()` or `Nette\Routing\RouteList::withPath()`; previously it threw `Error` for an uninitialized property (since 3.1.0) (56fcc1c2)

## 3.1.1

- **Changed** `Nette\Routing\Route::param2path()` now leaves the RFC 3986 pchar characters `!$&'()*+,;=:@` unencoded; previously everything except the slash was percent-encoded, so generated URLs differ for the same parameter value (5b0782d3)

## 3.1.0

- **Renamed** `$flags` of `Nette\Routing\RouteList::add()` → `$oneWay`; the old name was removed (f7419bc1)
- **Renamed** `$flags` of `Nette\Routing\RouteList::addRoute()` → `$oneWay`; the old name was removed (f7419bc1)
- **Renamed** `$flags` of `Nette\Routing\RouteList::prepend()` → `$oneWay`; the old name was removed (f7419bc1)
- **Deprecated** `Nette\Routing\Route::VALUE`: use `Nette\Routing\Route::Value` (alias since 3.0.3); the old name is silently deprecated (3059fcb8)
- **Deprecated** `Nette\Routing\Route::PATTERN`: use `Nette\Routing\Route::Pattern` (alias since 3.0.3); the old name is silently deprecated (3059fcb8)
- **Deprecated** `Nette\Routing\Route::FILTER_IN`: use `Nette\Routing\Route::FilterIn` (alias since 3.0.3); the old name is silently deprecated (3059fcb8)
- **Deprecated** `Nette\Routing\Route::FILTER_OUT`: use `Nette\Routing\Route::FilterOut` (alias since 3.0.3); the old name is silently deprecated (3059fcb8)
- **Deprecated** `Nette\Routing\Route::FILTER_TABLE`: use `Nette\Routing\Route::FilterTable` (alias since 3.0.3); the old name is silently deprecated (3059fcb8)
- **Deprecated** `Nette\Routing\Route::FILTER_STRICT`: use `Nette\Routing\Route::FilterStrict` (alias since 3.0.3); the old name is silently deprecated (3059fcb8)
- **Signature** native types added: `Nette\Routing\RouteList::add()`, `Nette\Routing\RouteList::withDomain()` and `Nette\Routing\RouteList::withPath()` now declare the return type `static`, `Nette\Routing\RouteList::end()` declares `?self`, and the properties `Nette\Routing\RouteList::$parent` and `Nette\Routing\Route::$defaultMeta` are typed; affects subclasses overriding or redeclaring them (7fe08095, 0301df21)
- **Signature** `$metadata` of `Nette\Routing\RouteList::addRoute()` is now declared `array`; previously untyped, so passing a non-array throws `TypeError` (7fe08095)
- **Changed** `Nette\Routing\Route` no longer uses `Nette\SmartObject`; magic property access through getters and setters and the guard against writing undeclared properties are gone (640d7124)
- **Changed** `Nette\Routing\RouteList` no longer uses `Nette\SmartObject`; magic property access through getters and setters and the guard against writing undeclared properties are gone (640d7124)
- **Changed** `Nette\Routing\SimpleRouter` no longer uses `Nette\SmartObject`; magic property access through getters and setters and the guard against writing undeclared properties are gone (640d7124)
- **Changed** `Nette\Routing\RouteList::end()` now throws `Error` when called on a list that was never created by `Nette\Routing\RouteList::withDomain()` or `Nette\Routing\RouteList::withPath()`; previously it returned `null` (0301df21)

## 3.0.5

- **Replaced** `Nette\Routing\RouteList::match()` with `Nette\Routing\RouteList::prepareRequest()` and `Nette\Routing\RouteList::completeParameters()`: `Nette\Routing\RouteList::match()` is marked `@final` and delegates to these two protected hooks; subclasses that overrode it to filter the request or to post-process matched parameters must override the hooks instead (1e90961f)
- **Changed** `Nette\Routing\RouteList::withPath()` now also matches a request path equal to the prefix without its trailing slash; previously only a path starting with the exact prefix matched (a9aaa014)

## 3.0.3

- **Renamed** `Nette\Routing\Route::VALUE` → `Nette\Routing\Route::Value`; the old name still works as an alias (2b00e8b0)
- **Renamed** `Nette\Routing\Route::PATTERN` → `Nette\Routing\Route::Pattern`; the old name still works as an alias (2b00e8b0)
- **Renamed** `Nette\Routing\Route::FILTER_IN` → `Nette\Routing\Route::FilterIn`; the old name still works as an alias (2b00e8b0)
- **Renamed** `Nette\Routing\Route::FILTER_OUT` → `Nette\Routing\Route::FilterOut`; the old name still works as an alias (2b00e8b0)
- **Renamed** `Nette\Routing\Route::FILTER_TABLE` → `Nette\Routing\Route::FilterTable`; the old name still works as an alias (2b00e8b0)
- **Renamed** `Nette\Routing\Route::FILTER_STRICT` → `Nette\Routing\Route::FilterStrict`; the old name still works as an alias (2b00e8b0)
