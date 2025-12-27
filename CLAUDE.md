# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Nette Routing** is a bidirectional URL routing library for PHP that provides two-way conversion between URLs and application parameters.

- **URL Parsing (Incoming)**: Converting HTTP requests into parameter arrays
- **URL Generation (Outgoing)**: Building URLs from parameter arrays

The routing layer is completely decoupled from application logic, allowing URL structure to be designed or changed without modifying code.

**Key Characteristics:**
- PHP 8.1-8.5 support
- Current branch: v3.1
- Triple licensed: BSD-3-Clause, GPL-2.0, GPL-3.0
- Dependencies: `nette/http` (^3.2 || ~4.0.0), `nette/utils` (^4.0)

## Essential Commands

### Testing

```bash
# Run all tests
composer run tester
# or
vendor/bin/tester tests -s

# Run specific test file
vendor/bin/tester tests/Route/basic.phpt -s

# Run tests in specific directory
vendor/bin/tester tests/Route/ -s
```

### Static Analysis

```bash
# Run PHPStan (level 5)
composer run phpstan
```

### Development Setup

```bash
# Install dependencies
composer install
```

## Architecture and Core Concepts

### Core Components

**Router Interface** (`src/Routing/Router.php`):
```php
interface Router {
    // URL → parameters (incoming request)
    function match(IRequest $httpRequest): ?array;

    // Parameters → URL (link generation)
    function constructUrl(array $params, UrlScript $refUrl): ?string;
}
```

**Route** (`src/Routing/Route.php` - 638 lines):
- Main route implementation with mask parsing
- Handles parameters, optional sequences, filters, validation patterns
- Supports wildcards (`%host%`, `%domain%`, `%tld%`, `%sld%`, `%basePath%`)
- Filter system: `FilterIn`, `FilterOut`, `FilterTable`, `FilterStrict`

**RouteList** (`src/Routing/RouteList.php` - 306 lines):
- Router collection/broker with intelligent caching
- Hierarchical organization via `withDomain()`, `withPath()`, `end()` fluent API
- Cache optimization with `warmupCache()` for URL generation
- Routes are tried sequentially - order matters (most specific first)

**SimpleRouter** (`src/Routing/SimpleRouter.php` - 70 lines):
- Query-parameter based router (e.g., `?controller=Product&action=detail&id=123`)
- Fallback when mod_rewrite unavailable or user-friendly URLs not needed

### Route Mask Syntax

**Parameters:**
- `<name>` - required parameter
- `<name=default>` - optional with default value
- `<name pattern>` - with validation regex (e.g., `<id \d+>`)

**Optional Sequences:**
- `[optional/part]` - optional segment
- `[!required-optional]` - optional but preferred when generating URLs

**Wildcards:**
- `%host%` - whole host (e.g., www.example.com)
- `%domain%` - domain without subdomains (e.g., example.com)
- `%tld%` - top level domain (e.g., com)
- `%sld%` - second level domain (e.g., example)
- `%basePath%` - path to root directory

**Special Features:**
- Foo parameters: `<?.html>` - unnamed parameters matching regex
- Query parameters: `product ? id=<productId> & cat=<categoryId>`
- Global filters on empty string `''` key for cross-parameter transformations
- Dynamic routing with callbacks (see Advanced Usage section)

### URL Generation Strategy

RouteList implements intelligent caching:
1. Routes are ranked by constant parameters for faster lookup
2. Cache keys optimized based on most discriminating parameter
3. Auto-omits optional parts when possible
4. First matching route without `ONE_WAY` flag is canonical

### Filter System

**Parameter-specific filters:**
```php
Route::FilterTable => ['url-value' => 'param-value']  // Dictionary translation
Route::FilterIn => function(string $s): string {...}   // URL → parameter
Route::FilterOut => function(string $s): string {...}  // Parameter → URL
Route::FilterStrict => true                            // Strict validation mode
```

**Global filters** (on `null` key):
```php
'' => [
    Route::FilterIn => function(array $params): array {...},
    Route::FilterOut => function(array $params): array {...},
]
```
Custom filters execute before global `FilterIn`, but global `FilterOut` executes before custom.

**Built-in Parameter Filters:**
The parameters `presenter`, `action`, and `module` have predefined filters that automatically convert between:
- PascalCase/camelCase (in code) ↔ kebab-case (in URLs)
- Example: `ProductEdit` presenter → `product-edit` in URL
- When defining defaults, use the transformed form: `<presenter=product-edit>` not `<presenter=ProductEdit>`

## Advanced Usage

### Dynamic Routing with Callbacks

Routes can be assigned callback functions that execute when the path is visited:

```php
$router->addRoute('test', function () {
    echo 'You are at the /test address';
});

// With parameters
$router->addRoute('<lang cs|en>', function (string $lang) {
    echo match ($lang) {
        'cs' => 'Vítejte!',
        'en' => 'Welcome!',
    };
});
```

### Module Organization

For Nette Application usage, routes can be organized into modules:

```php
$router = new RouteList;
$router->withModule('Forum')
    ->addRoute('rss', 'Feed:rss')  // presenter will be Forum:Feed
    ->addRoute('<presenter>/<action>')

    ->withModule('Admin')  // nested: Forum:Admin module
        ->addRoute('sign:in', 'Sign:in');

// Alternative: use module parameter
$router->addRoute('manage/<presenter>/<action>', [
    'module' => 'Admin',
]);
```

### RouterFactory Pattern (Nette Application Integration)

Standard way to integrate router into DI container:

```php
namespace App\Core;

use Nette\Application\Routers\RouteList;

class RouterFactory
{
    public static function createRouter(): RouteList
    {
        $router = new RouteList;
        $router->addRoute('rss.xml', 'Feed:rss');
        $router->addRoute('<presenter>/<action>[/<id>]', 'Home:default');
        return $router;
    }
}
```

Configuration in `services.neon`:
```neon
services:
    - App\Core\RouterFactory::createRouter
```

Dependencies are passed via autowiring:
```php
public static function createRouter(Nette\Database\Connection $db): RouteList
{
    // Can use $db for dynamic route generation
}
```

### Standalone Usage (Without Nette Application)

When using routing without presenters:

```php
use Nette\Routing\RouteList;

$router = new RouteList;
$router->addRoute('rss.xml', [
    'controller' => 'RssFeedController',
]);
$router->addRoute('article/<id \d+>', [
    'controller' => 'ArticleController',
]);

// Match incoming request
$params = $router->match($httpRequest);
if ($params === null) {
    // no matching route found, send 404
    exit;
}

$controller = $params['controller'];

// Generate URLs
$url = $router->constructUrl(['controller' => 'ArticleController', 'id' => 123], $httpRequest->getUrl());
```

## Testing Conventions

**Test Structure:**
- All tests use `.phpt` extension
- Located in `tests/` directory organized by feature
- Uses custom helper functions from `tests/bootstrap.php`

**Test Helper Functions:**

```php
// Wrapper for descriptive test cases
test('description of what is being tested', function() { ... });

// Test both URL matching and link generation
testRouteIn(Router $route, string $relativeUrl, ?array $expectedParams, ?string $expectedUrl);

// Test only URL generation
testRouteOut(Router $route, array $params): ?string;
```

**Test Categories:**
- `tests/Route/` - Route class features (optional params, sequences, filters, etc.)
- `tests/RouteList/` - RouteList functionality (caching, domain/path scoping)
- `tests/SimpleRouter/` - SimpleRouter tests

**Testing Pattern:**
Each test validates bidirectional behavior - both matching incoming URLs and generating outgoing URLs.

## Code Conventions

**Standard Requirements:**
- All PHP files start with `declare(strict_types=1)`
- Follow Nette Coding Standard (based on PSR-12)
- Use `use function` to import specific functions
- Full type declarations on all parameters, properties, return values

**Route-Specific Patterns:**
- Private constants for internal state (e.g., `Fixity`, `Default`, `FilterTableOut`)
- Protected methods allow extensibility
- Extensive use of PHP 8.1+ features (readonly properties, named parameters)

**Import Style:**
```php
use Nette\Http\IRequest;
use Nette\Http\UrlScript;
use function is_string, preg_match, str_contains;
```

## CI/CD Pipeline

GitHub Actions workflow runs:
1. **Tests** - PHP 8.1, 8.2, 8.3, 8.4, 8.5 matrix
2. **Lowest Dependencies** - Ensures minimum version compatibility
3. **Code Coverage** - Reports sent to Coveralls
4. **Coding Style** - Automated style validation
5. **Static Analysis** - PHPStan level 5

Test artifacts are uploaded on failures for debugging.

## Important Implementation Notes

**Route Matching:**
- Sequential evaluation (first match wins)
- Order is critical: declare routes from most specific to most general
- Use `ONE_WAY` flag for backward compatibility routes (matched but not generated)

**URL Generation:**
- First route without `ONE_WAY` flag is canonical
- Canonization prevents SEO duplication
- Auto-optimization omits optional parameters when possible

**Domain/Path Scoping:**
```php
$router = new RouteList;
$router->withDomain('admin.example.com')
    ->addRoute(...)
    ->addRoute(...)
->end()
->withDomain('example.com')
    ->withPath('export')
        ->addRoute(...)
```

**Caching:**
- `warmupCache()` pre-generates URL construction lookup tables
- Significantly improves link generation performance
- Cache key based on most discriminating parameter

## Performance and Optimization

**Router Performance:**
- Number of routes affects speed - keep under several dozen routes
- For complex URL structures, consider writing a custom router

**Production Caching:**
If router has no dependencies (no database, no constructor arguments), enable serialization caching:

```neon
routing:
    cache: true
```

This serializes the compiled router form directly into the DI container, improving application startup time.

## Debugging and SEO

### Debugging Router (Tracy Integration)

The routing panel in Tracy Bar shows:
- **Green bar with ✓** - route that matched the current URL
- **Blue bars with ≈** - routes that would also match if green didn't win
- Current presenter & action parameters
- Redirect information for canonization debugging

**Development Tips:**
- Open Developer Tools (Ctrl+Shift+I / Cmd+Option+I)
- Disable cache in Network panel to see redirects properly
- Check the *redirect* bar to see how router understood URL before canonization

### SEO and Canonization

**Automatic Canonization:**
When multiple URLs lead to same destination (e.g., `/index` and `/index.html`):
1. Framework designates first matching route as primary (canonical)
2. Other URLs redirect to canonical with HTTP 301
3. Prevents search engines from indexing duplicate content

**Canonical URL Rules:**
- Generated by first matching route **without** `ONE_WAY` flag
- List primary routes first in RouteList
- Canonization performed by presenter/controller layer

**ONE_WAY Flag Usage:**
```php
// Old URL - accepted but not generated
$router->addRoute('product-info', 'Product:detail', $router::ONE_WAY);
// New canonical URL
$router->addRoute('product/<id>', 'Product:detail');
```

When old URL accessed, automatic 301 redirect to new URL occurs.

### HTTPS Configuration

**Protocol Handling:**
- Router generates URLs with same protocol as current page
- No special configuration needed for HTTPS in most cases

**Protocol-Specific Routes:**
```php
// Force HTTP for specific route
$router->addRoute('http://%host%/<presenter>/<action>', ...);

// Force HTTPS for specific route
$router->addRoute('https://%host%/<presenter>/<action>', ...);
```

**Server-Level HTTPS Redirect:**
Redirect entire site to HTTPS via `.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule .* https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

## Usage Context

**Two Main Use Cases:**

1. **With Nette Application** (presenters, actions)
   - Use `Nette\Application\Routers\RouteList`
   - Routes like: `$router->addRoute('article/<id>', 'Article:view')`
   - Parameters: `presenter`, `action`, `module`
   - Integration via RouterFactory in DI container

2. **Standalone** (REST APIs, custom frameworks)
   - Use `Nette\Routing\RouteList`
   - Routes like: `$router->addRoute('article/<id>', ['controller' => 'ArticleController'])`
   - Custom parameter names (controller, handler, etc.)
   - Direct instantiation or DI container integration

This library (`nette/routing`) is the standalone version. For Nette Application integration, the framework provides additional layers on top of this routing core.

## Documentation Resources

- Full documentation: https://doc.nette.org/routing
- API reference: https://api.nette.org/routing/
- readme.md contains comprehensive usage examples
