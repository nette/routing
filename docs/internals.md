# Routing internals

How `nette/routing` works underneath, for agents refactoring it. This is the
standalone core (`Nette\Routing\Route`, `RouteList`, `SimpleRouter`); the
`Nette\Application\Routers\*` subclasses add presenter/module conventions on top
and are out of scope here.

## The bidirectional contract

`Router` (`src/Routing/Router.php`) has exactly two directions that must stay
mutually consistent:

- `match(IRequest): ?array` — URL to parameters, `null` = "not mine".
- `constructUrl(array, UrlScript): ?string` — parameters to absolute URL,
  `null` = "I cannot build this".

A `Route` compiles its mask **once in the constructor** into two independent
representations, one per direction, and never re-parses at runtime:

- `$re` (+ `$aliases`, regex-group to param-name) drives `match()`.
- `$sequence` (a flat list of literals, parameter names, and bracket markers)
  drives `constructUrl()`.
- `$metadata` (per-param value, fixity, filters, pattern, precomputed default)
  is shared by both.

When you touch generation, `$sequence` is the source of truth; when you touch
matching, `$re`. They are built together in `parseMask()` and can drift apart if
edited independently.

## Mask compilation (`Route::parseMask`)

The single trap that dominates this file: **`parseMask` is a reverse single-pass
parser** walking the output of `Strings::split()` **backwards**. The split regex
has four capturing groups (name, `=default`, pattern, and the bracket/query
token), so the flat array comes in **5-element groups** (one literal + four
capture slots). The loop consumes them with hard-coded index arithmetic:
`$i -= 4` per bracket, three `$i--` per parameter, and `$i -= 5` to skip a
trailing query segment (after `parseQuery`).

- **The offsets are coupled to the number of capturing groups in the split
  regex.** Add or remove a group in that pattern and every `$i -= N` silently
  reads the wrong slot. This is why `phpstan.neon` carries **6
  `offsetAccess.notFound` ignores** for this file — PHPStan cannot see the
  invariant, and neither can a casual reader. Treat the parser and its split
  regex as one atomic unit.

Facts established during compilation that are non-obvious later:

- **Fixity is a three-state private constant** (`InQuery=0`, `InPath=1`, `Constant=2`)
  that controls matching precedence and overridability. `Constant` = value given
  in metadata but **not** present in the path mask, so it can never be changed by
  URL or query. `InPath` = optional parameter with a default. This state, not the
  presence of a default alone, is what later code branches on.
- **Auto-optional propagates right-to-left.** A trailing run of parameters that
  have defaults is wrapped in `(?:...)?` and made optional — but only until the
  first parameter *without* a default is seen (scanning from the right, so
  `$autoOptional` flips off). This is why `<presenter=Home>/<action=default>/<id>`
  behaves as if nested in brackets: optionality is inherited leftward, and order
  matters.
- **Wildcards stay as placeholders in `$re`.** `%host%`, `%domain%`, `%tld%`,
  `%sld%`, `%basePath%` are *not* resolved at parse time; they remain literal
  tokens in the compiled regex and are substituted per-request in `match()` /
  `constructUrl()` via `strtr` against the actual host. The compiled regex is a
  template, not a finished pattern.
- **Everything is a string after construction.** `normalizeMetadata` casts
  scalar defaults to strings right in the constructor (`false` → `'0'`), and
  `preprocessParams` casts incoming scalars the same way, so all default and
  constant comparisons are string `===`. `getDefaults()` therefore returns
  strings, and `['id' => 12]` equals a default of `'12'`.
- **`<?name pattern>` is a one-way literal** ("foo" parameter). It compiles to a
  non-capturing alternation `(?:name|pattern)` in `$re`; in `$sequence` the
  literal text is baked into the adjacent literal and the `?name` marker is
  skipped by `compileUrl` — it matches the alternatives but always generates
  the literal.
- **Query parameters are parsed separately** (`parseQuery`), have no
  validation pattern, and can be renamed via `$xlat` (param name ↔ query key).
  They never enter `$sequence`.

## Matching (`Route::match`)

The parameter array is assembled in a **fixed precedence order**, documented by
the inline comment "mask, fixity, query, defaults":

1. Path parameters captured by `$re`.
2. Constant/in-path fixity params seeded to `null` (so query cannot overwrite
   them, and step 4 can still fill their default).
3. Query parameters (renamed through `$xlat`).
4. Per-parameter filters + remaining fixity defaults, **then** the global
   `''` `FilterIn`.

Reordering these steps changes which source wins for a given parameter; this is
the routing precedence model and is not visible from any single method.

Traps:

- **The mask scheme is generation-only.** `detectMaskType` records `https?:`
  from the mask into `$scheme`, but `match()` never checks the request scheme —
  an `https://…` mask happily matches a plain-http request. The scheme is
  applied only in `constructUrl` (falling back to the reference URL's scheme).
- **Trailing slash is normalized in, not out.** `match()` appends `/` to the
  path and `$re` ends in `/?$`, so `/foo` and `/foo/` both match. A custom
  pattern that anchors on the raw path will misbehave.
- **Filter order is asymmetric** (also see generation): on the way *in*,
  per-parameter `FilterIn` runs **before** the global `FilterIn`.
- **`FilterStrict` rejects, absent translation passes through.** A `FilterTable`
  miss returns `null` (route declines) only when `FilterStrict` is set;
  otherwise the original value survives.

## URL generation (`Route::constructUrl`, `preprocessParams`, `compileUrl`)

`preprocessParams` validates and normalizes the parameter array, then
`compileUrl` walks `$sequence` **backwards** filling literals and parameters and
opening/closing bracket levels.

- **Auto-optional / `$required` bookkeeping.** `compileUrl` omits an optional
  segment unless a more specific (deeper/later) parameter forced its level
  `required`. The shortest URL wins. `[!` marks a bracket "required-optional":
  kept during generation even when it could be dropped. This mirrors the parse
  step's auto-optional rule from the other side.
- **Constant params are a hard gate.** In `preprocessParams`, a supplied value
  that disagrees with a `Constant`-fixity default returns `false` (route cannot
  generate). Matching the default value causes the param to be *dropped* from the
  output (it is implied by the route).
- **The default path filter refuses non-scalars.** `param2path` (the built-in
  `FilterOut` in `defaultMeta['#']`) cannot encode an array/object into a path
  segment; `preprocessParams` returns `false` rather than emitting garbage.
  Non-scalars are safe only as query parameters, where they end up in
  `http_build_query`.
- **Empty string counts as absent.** `compileUrl` treats `''` like a missing
  value (`$params[$name] !== ''`): a required parameter given `''` fails
  generation, an optional one falls back to its default or is omitted.
- **Pattern is re-checked on the way out.** After filters, the value must be
  scalar and still match the parameter's compiled `Pattern` (tested against
  the `rawurldecode`d value), otherwise generation returns `null`.
- **Filter order is asymmetric the other way:** on the way *out*, the global
  `''` `FilterOut` runs **before** per-parameter `FilterOut`. Together with the
  matching-side rule, this guarantees a parameter's own filter always sits
  *closest* to the URL and the global filter closest to the application.

## Route collection (`RouteList`)

`RouteList` tries its routers in order for matching and uses a lazily built
lookup index for generation.

- **Generation index (`warmupCache`).** Routers are grouped by the value
  of the **single most-discriminating constant parameter** — the param name whose
  set of distinct constant values across the list is largest becomes
  `$cacheKey`. `constructUrl` then only iterates the bucket for the requested
  value (falling back to `'*'`). Two subtleties that will bite:
  - A router that has **no constant value on the chosen key** is added to
    **every** bucket (`$value === null` → `array_keys($ranks)`). Generic routes
    and nested `RouteList`s (which expose no constant params) are therefore tried
    under all keys.
  - **`ONE_WAY` routes are excluded from the index entirely**, so they never
    generate URLs — this is the mechanism behind canonization: the first
    non-one-way matching route is the canonical generator. (The 301 redirect
    itself is the presenter's job, not this package's.)
- **Cache invalidation is by nulling `$ranks`.** Every mutation (`add`,
  `prepend`, `modify`) resets `$ranks = null`; the next `constructUrl` re-warms.
  Nested lists are warmed recursively.
- **The tree is built by `withDomain`/`withPath`, which return the CHILD;**
  `end()` returns the parent. The child carries the domain/path constraint and
  `prepareRequest()` strips the path prefix / checks the domain before delegating
  to it. Matching descends the tree; a failed constraint returns `null` early.
  (`$parent` is initialized only by `withDomain`/`withPath`, so `end()` on the
  root list throws an uninitialized-property `Error`.)
- **Serialization has no custom hooks.** `routing: cache: true` in
  `nette/application` serializes the whole compiled router into the DI container
  and reconstructs it via `unserialize`, bypassing the constructor; it works only
  when no closures live in the metadata (filters/callbacks). The `refUrlCache`
  memo (an `SplObjectStorage` of expanded reference URLs, keyed by `UrlScript`
  instance) serializes along with the rest. It holds strong references — each
  distinct `UrlScript` adds an entry, which matters only in long-running
  processes.

## SimpleRouter

Query-parameter routing with no mask. `match()` succeeds **only when the path
info is empty** (i.e. everything after the base path); it returns query +
defaults. Generation drops any parameter equal to its default. Nothing here is
non-obvious beyond the empty-path-info condition.

## Extension surface (why some `protected`/`@internal` members exist)

`Nette\Application\Routers\Route` and `RouteList` subclass this core to add
presenter/action/module conventions, and they depend on members that look
internal but are a **stable contract**:

- **`protected array $defaultMeta`** — the per-parameter default metadata table.
  The Application `Route` overrides it to inject the presenter/action/module
  kebab-case filters. Core deliberately ships only the `'#'` path default
  (`param2path`).
- **`getMetadata()`** (protected, `@internal`) and **`getConstantParameters()`**
  (public, `@internal`). `RouteList::warmupCache` calls
  `getConstantParameters()` on every `Route` to build the generation index, so
  its meaning (params with a fixed constant value) is a cross-class invariant,
  not a local helper.
- **`RouteList::completeParameters()`** — a protected post-match hook (identity
  here). The Application `RouteList` overrides it to apply the module prefix to
  matched parameters; returning `null` from it makes `match()` continue with
  the next router.

Changing the shape of these breaks `nette/application` even though nothing in
this package references them that way.

## Navigation map

| Concern | Where |
|---|---|
| Two-way contract | `Router.php` |
| Mask to regex + sequence | `Route::parseMask`, `parseQuery` |
| Match precedence, filters, method check | `Route::match` |
| URL build, auto-optional, constant gate | `Route::constructUrl`, `preprocessParams`, `compileUrl` |
| Host wildcard substitution | `Route::hostParts`, the `strtr` in `match`/`constructUrl` |
| Collection order, generation index, tree | `RouteList` (`warmupCache`, `prepareRequest`, `withDomain`/`withPath`) |
