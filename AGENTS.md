# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

The engine is small but dense: mask compilation, matching precedence, and the
generation cache are expensive-to-reconstruct traps. Read `docs/internals.md`
before refactoring `Route` or `RouteList`.

## Project Overview

**Nette Routing** is the bidirectional URL routing core: `match()` turns a request
into a parameter array, `constructUrl()` builds a URL from parameters. This is the
standalone package (`Nette\Routing\*`); `Nette\Application\Routers\*` subclasses
add the presenter/action/module conventions on top and live in `nette/application`.

- **PHP Version**: 8.1 - 8.5
- **Package**: `nette/routing` (deps: `nette/http`, `nette/utils`)

## Essential Commands

```bash
# Run all tests
vendor/bin/tester tests -s        # or: composer tester
vendor/bin/tester tests/Route/ -s

# Static analysis (PHPStan level 8)
composer phpstan
```

## Conventions

- Every file starts with `declare(strict_types=1);`; import functions with
  `use function ...`; private constants for internal state (`Fixity`, `Default`);
  Nette Coding Standard.
- Tests are Nette Tester `.phpt` under `tests/`; `tests/bootstrap.php` provides
  `testRouteIn()` / `testRouteOut()`, and every route is tested **both directions**
  (match and generate). `testRouteIn()` injects an extra `test=testvalue` query
  parameter into every request (expected params must include it) and drops the
  `extra` param before the reverse check.

## Working in this repo

- **A `Route` compiles its mask ONCE in the constructor into two independent
  representations** - `$re` (+ aliases) drives `match()`, `$sequence` drives
  `constructUrl()`; `$metadata` is shared. Edit one direction and you must keep the
  other consistent.
- **`parseMask` is a reverse single-pass parser whose index arithmetic (`$i -= 4/5`)
  is coupled to the number of capture groups in its split regex** - that is why
  `phpstan.neon` carries 6 `offsetAccess.notFound` ignores. Treat the parser and its
  regex as one atomic unit.
- **Fixity is a three-state private constant (`InQuery`/`InPath`/`Constant`)** that drives
  matching precedence and overridability; auto-optional parameters propagate
  right-to-left. Match assembles params in a fixed order (mask, fixity, query,
  defaults) - reordering changes which source wins.
- **Filter order is asymmetric:** on the way *in*, per-parameter `FilterIn` runs
  before the global `''` `FilterIn`; on the way *out*, the global `FilterOut` runs
  first.
- **`RouteList` builds a generation index (`warmupCache`) bucketed by the single
  most-discriminating constant parameter;** one-way routes are excluded from it -
  that exclusion *is* the canonization mechanism (the 301 is the presenter's job).
  Serialization (`routing: cache: true`) bypasses the constructor and only works
  when no closures live in the metadata.
- **`protected $defaultMeta` and `@internal getMetadata()`/`getConstantParameters()`
  are a stable contract** that `nette/application` subclasses depend on - changing
  their shape breaks it even though nothing here references them that way.
- User-facing how-to (mask syntax, filter usage, modules, `RouterFactory`, standalone
  usage, HTTPS/SEO/canonization) is manual material and lives in the public web docs.
