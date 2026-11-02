# STEP 4 PLAN — Laravel 11.57 → 12.x, PHP stays 8.4, Carbon 2 → 3

**Analysis only (2026-10-29). No code, config, test or database file was changed.**
Branch `upgrade/step-4` created from `development` (`142cbcc`, = `origin/development`, step 3 merged). Not committed, not pushed.

Everything below was checked against real code, not only read, in the **throwaway trial** container `s3trial` (PHP 8.4.27 image, code = `git archive HEAD`, own vendor; the working tree, the `vendor` volume and the dev containers were not touched; artisan / PHPUnit there as `www-data`):
- a **trial resolution and install of Laravel 12.70.2** (explicit package list, no `-W`) and on it, with and without each compat change below: the full suite, the **golden master (429 URLs) served by the trial** and compared with `before-step-4`, the booted-app **dump-and-diff L11 vs L12** (every config value, all 278 routes with middleware, HTTP middleware / groups / priority / aliases, events, providers), the **route-name resolution of every duplicated route name**, `uri()` of **4,488 model × locale combinations**, the compiled PHP of all **20 JSON-LD views**, a **class load** of all project / pinned-copy classes, a **method-collision check** (170 methods new in L12), the Excel export harness, the runtime deprecation log;
- source diffs of the framework classes our code depends on (S3 adapter, FilesystemManager, Translator, MessageSelector, FileLoader, RouteCollection, RouteUrlGenerator, SessionGuard, LengthAwarePaginator, BladeCompiler) and of the candidate genealabs / pivot-events versions.

## 1. Baseline (current state: Laravel 11.57.1, PHP 8.4.27, Carbon 2.73.0)

| Check | Result |
|---|---|
| Full suite (Docker, as `www-data`) | **75 tests, 592 assertions, OK** |
| Golden master `before-step-4` (429 URLs, 2026-10-29) | 414 × 200, 14 × 404, 1 × 500 (`/api/news/1019/files`, the same 500 as in every capture) |
| `before-step-4` vs `after-step-3e` | **429 / 429 identical** (no data change since step 3). Reference for step 4 is `before-step-4` |

## 2. Summary

- **Laravel 12 alone (no compat) breaks the public site:** on the trial **65 of 429 URLs return 500** and 20 admin API lists change. Four causes, all found by the golden master (none by the suite) — §3.3–§3.6:
  1. new **Blade `@context` directive** swallows the JSON-LD `"@context"` text in 20 views → PHP syntax error (15 pages);
  2. **`route()` rejects an empty parameter**: the language switcher calls `$model->uri('en')` for news without an English slug (49 pages);
  3. **duplicate route names now resolve to the first route** instead of the last (9 names; no visible output today);
  4. the paginator JSON gets a new **`page` key** in `links` (20 admin API lists).
  Plus Carbon 3 removes **`formatLocalized()`** (§3.2), reachable only through code paths no page uses today.
- **Required (proposed fixes, none in `Modules/`, views, Vue/JS/CSS, `package.json` or the database):** composer (Laravel 12, Carbon 3, genealabs 12.0.4, prompts 0.3); `AppServiceProvider` + two tiny classes in `app/` (`@context`, route lookups, paginator links); two patches in the pinned core (`Base::uri()` empty slug, `formatLocalized` compat). **With all of them the trial golden master is identical on 428 / 429 URLs** — the one difference is the known capture artefact of the trial (§3.10).
- **Not required (verified):** Dockerfile / PHP (stays 8.4), the Laravel skeleton, `App\Filesystem\AwsS3V3Adapter` (R22, §3.8), the Translator `@choice` patch and `MixedLoader` (still compatible), `config/*`, the D7 local-disk callback, ultrono, typidesign, tinker, debugbar, ignition, laravel/ui, Permission, eloquent-sortable, Excel, Croppa, QB 6.4.4 bumps, PHPUnit / collision.
- **CLAUDE.md check — modules, views, Vue, frontend, `package.json`, database, look and behaviour:** **nothing in `Modules/`, `resources/`, `public/`, `routes/`, `database/` or the JS/CSS build changes.** Visible output stays identical **only with D-S4-3 … D-S4-6**; without them public pages return 500. One behaviour change remains that cannot be pinned reasonably: remember-me cookies after a password change (§3.7, D-S4-8). No database write anywhere (genealabs cache keys change → `cache:clear`, a cache, not the DB).
- **Decisions for you** (§8).

## 3. Items

### 3.1 composer.json (L12)

Trial resolution, `composer update` with an explicit list, **no `-W`**:

| Package | Now (locked) | Target | Why / note |
|---|---|---|---|
| php | ^8.4 | = | L12 needs ^8.2; Docker stays `php:8.4-apache-bookworm` (8.4.27) |
| laravel/framework | ^11.0 (11.57.1) | **^12.0** → locks **v12.70.2** (2026-10-27, latest 12.x) | requires Carbon ^3.8.4, prompts ^0.3, serializable-closure ^1.3\|^2.0, Symfony ^7.2 (→ stays 7.4) |
| nesbot/carbon (transitive) | 2.73.0 | **3.15.1** | required by L12 (§3.2) |
| carbonphp/carbon-doctrine-types (transitive) | 2.1.1 | 3.2.1 | Carbon 3 |
| genealabs/laravel-model-caching | ^11.0.1 | **^12.0.4** (12.0.4) | D-S4-1, §3.9; 11.0.1 allows only L10–L11 |
| genealabs/laravel-pivot-events → mikebronner/laravel-pivot-events | 11.0.0 | **mikebronner 12.0.0** | package renamed by its author; `src/` byte-identical to genealabs 11.0.0 (same namespace) |
| laravel/prompts (transitive, console only) | 0.1.25 | 0.3.26 | required by L12 |
| new (transitive) | | `symfony/clock` **v7.4.21**, `symfony/polyfill-php84`, `symfony/polyfill-time` | Carbon 3 requires `symfony/clock ^6.3.12\|^7\|^8`; Composer's free choice is **v8.1.9** → kept on 7.4 LTS with a temporary `symfony/clock:^7.4` constraint during the update (same technique as step 3, D-S4-2) |
| **Unchanged and verified for L12** | | Symfony 7.4.x (translation 6.4.49: Carbon 3 allows it), laravel/serializable-closure 1.3.7, laravel/ui 4.6.3, spatie/laravel-permission 6.25.0, spatie/eloquent-sortable 4.5.2 (`^12`, Carbon ^2.63\|^3), maatwebsite/excel 3.1.70, bkwld/croppa 8.0.1, laracasts/presenter 0.2.9, laracasts/utilities 3.2.5, msurguy/honeypot 1.5.0, ultrono/laravel-sitemap 9.5.0 (`^12`), typidesign 1.0.4, laravel/tinker 2.11.1 (`^12`), barryvdh/laravel-debugbar 3.16.0 (`^12`), spatie/laravel-ignition 2.9.1 (`^12`), phpunit 11.5.57, collision 8.9.5, league/flysystem-aws-s3-v3 3.32.0 + aws/aws-sdk-php **3.337.3 (pin kept, O1)**, Sidebar VCS fork `09d438e`, all pinned copies (constraints already ^12–^13) | |

Trial lock result: **5 updates, 4 installs, 1 removal** (all listed above). Command (for 4f), after `composer require --no-update laravel/framework:^12.0 genealabs/laravel-model-caching:^12.0.4` and temporarily `symfony/clock:^7.4` (+ the temporary Sidebar `no-api`, both removed before the commit, lock `content-hash` via Composer's `Locker::getContentHash()`, as in step 3):
`composer update laravel/framework genealabs/laravel-model-caching genealabs/laravel-pivot-events nesbot/carbon carbonphp/carbon-doctrine-types laravel/prompts symfony/clock`

### 3.2 Carbon 3 (required by L12) — what could change, and `formatLocalized` (R5)

**Scanned** in `app/`, `Modules/`, `packages/`, `config/`, `routes/` and all Blade views: no `diffForHumans`, `diffIn*`, `translatedFormat`, `isoFormat`, `locale()`, `Carbon::setLocale`, `createFromTimestamp`, `startOfWeek` / week functions, `setTestNow`, `setUtf8` in our code; dates in views are rendered with `->format()` (unchanged in Carbon 3); model dates are serialized by Laravel (`serializeDate`). So the Carbon 3 behaviour changes (signed float `diffIn*`, UTC `createFromTimestamp`, locale-based week start, new translations / `diffForHumans` wording for sr / sr_Latn) **do not reach our output**. Verified: `DateCastsTest` (5 models: Carbon on read, stored format, JSON, dirty check) and every date in the golden master (public pages and `/api` JSON) are identical on Carbon 3. Laravel's own uses of Carbon (cache TTL, sessions, cookies, password reset tokens) are adapted by L12 itself; genealabs 12.0.4 already uses `diffInSeconds(…, true)` for Carbon 3.

**`formatLocalized()` was removed in Carbon 3.** It is called only in the pinned core:

| Call | Used by | Today |
|---|---|---|
| `Presenter::dateLocalized()` (`'%e %B %Y'`) | models whose ModulePresenter does **not** override it | the 5 modules rendering `dateLocalized` in views (News, Announcements, Advertismentboards, Biographies, Pages) override it with `format('d.m.Y')` → **no view reaches the core method today** |
| `Presenter::dateTimeLocalized()` (`'%e %B %Y %H:%M'`) | — | not used in any view |
| `Services\Dates::concat()` | — | no caller in the project |

On Carbon 3 each of them throws `Carbon\Exceptions\UnknownMethodException: Method formatLocalized does not exist` (verified) → a 500 the moment any page or future template uses them. **Proposed compat (pinned core, D-S4-7):** `Services\Dates::formatLocalized(DateTimeInterface $date, string $format)` = Carbon 2.73's own implementation (Windows `%e` branch, `strftime($format, strtotime(<Y-m-d H:i:s>))`, strict mode = no `@`, as Carbon 2's default; the `$utf8` branch is never active here), used by `Presenter::dateLocalized()` / `dateTimeLocalized()` and `Dates::concat()`; `PATCHES.md`. **Verified on the trial: output byte-identical to Carbon 2** for 4 dates × both presenter methods and 4 `concat()` ranges, e.g. `" 5 October 2026"` (leading space of `%e`), `" 5 October 2026 08:00"`, `"Monday 05 to Wednesday 07 October 2026"`, `"Tuesday 30 December 2025 to Friday 02 January 2026"`. The server runs in the `C` locale (core `SetLocale` builds invalid locale names, `setlocale()` fails, as in all steps), so the names are English, as today. `strftime()` is deprecated (logged to `null`) exactly as Carbon 2 called it.

**Test first (4t):** `Presenters/LocalizedDatesTest` — the strings above for a model through the core `Presenter` and `Dates::concat()`; no database. Green on L11 + Carbon 2; **fails on Carbon 3 without the compat** (`UnknownMethodException`).

### 3.3 Blade `@context` directive (found by the golden master) — D-S4-3

| | |
|---|---|
| Found | 15 pages 500 on the trial: `syntax error, unexpected end of file, expecting "elseif" or "else" or "endif"` in `resources/views/vendor/<module>/public/_itemlist-json-ld.blade.php` |
| Cause | Laravel 12 adds the Blade directives **`@context` / `@endcontext`** (Context feature), plus `@hasStack`, `@includeIsolated`. The JSON-LD text `"@context":"https://schema.org"` is now compiled as an unclosed `@context` block. Blade matches directives case-insensitively |
| Where | **20 published views**: `resources/views/vendor/{advertismentboards, announcements, banners, biographies, categories, classschedules, departments, galleries, news, newsletters}/public/_json-ld.blade.php` and `…/_itemlist-json-ld.blade.php`. No view uses `@endcontext`, `@hasStack`, `@includeIsolated` (searched, case-insensitive, all `.blade.php` in `resources/`, `Modules/`, `packages/`) |
| Proposed fix (no view change) | `AppServiceProvider::boot()`: `Blade::directive('context', …)` returning the literal text `@context` (plus its parentheses if any). Blade checks custom directives **before** built-in ones, so `@context` is left as text, as in L8–L11 |
| Verified on the trial | the compiled PHP of **all 20 views is byte-identical to Laravel 11**; the 15 pages return 200 and match `before-step-4` |
| Alternative | edit the 20 views to `@@context` (renders `@context` on every version) — a view change, needs your approval (CLAUDE.md); not recommended |
| Test first (4t) | `Views/JsonLdContextTest`: render a JSON-LD partial (`news::public._json-ld` / `_itemlist-json-ld`) and assert the literal `"@context"` in the output; `Blade::compileString('"@context":"x"')` unchanged. Green on L11, fails on L12 without the fix |

### 3.4 `route()` with an empty parameter (found by the golden master) — D-S4-4

| | |
|---|---|
| Found | 49 pages 500 on the trial (news detail pages `/oglasnaploca/novosti/…`): `Missing required parameter for [Route: en::news] [URI: en/advertisementboard/news/{slug}]`, in `core/public/_lang-switcher.blade.php` |
| Cause | the language switcher renders `url($model->uri($locale))`; pinned core `Base::uri()` calls `route($locale.'::news', $this->translate('slug', $locale))`. For a news item without an English translation the slug is `''`. **L11: `route('en::news', '')` = `…/en/advertisementboard/news`** (the empty segment is dropped, the link points to the English news list). **L12** (`RouteUrlGenerator::formatParameters()` rewritten) treats `''` as a **missing** parameter and throws. Not a duplicate-name problem (`en::news` is unique) |
| Other `route()` calls with a possibly empty value | `Presenter.php:221` (`{!! module:id !!}` body links, `$model->slug` of a *published* item in the current locale), the prev/next `_items-navigator` views (`$prev->slug` / `$next->slug`, same), `Tag::uri()` (`$this->slug`). **Data check (read-only):** 0 published items with an empty slug in their own locale (news, announcements, advertismentboards, biographies), 0 of 11 tags with an empty slug → these paths cannot hit the case today; only `Base::uri()` *for another locale* does |
| Proposed fix (pinned core, no module / view change) | `Base::uri()`: if the translated slug is `''`, return `url(str_replace('{slug}', '', <route uri>))` — exactly what L11's `route()` produced (empty placeholder, trailing slash trimmed by `url()`); otherwise `route()` as before. `PATCHES.md` |
| Verified on the trial | **`uri()` of 4,488 model × locale combinations** (all News, Announcements, Advertismentboards, Classschedules, Tags × sr / sr-latn / en) **identical to L11**; the 49 pages return 200 and match `before-step-4` |
| Not chosen | a global `UrlGenerator` / `RouteUrlGenerator` replacement restoring L11's parameter handling everywhere (larger copied framework code, to be re-checked in L13); only needed if published items without a slug ever occur → noted as R-S4-4 |
| Test first (4t) | `Models/UriEmptySlugTest`: a News without an English slug → `uri('en')` = `http://localhost/en/news` (the BaselineSeeder news page), and the sr news detail page returns 200 with that link in the language switcher. Green on L11, fails on L12 without the fix |

### 3.5 Duplicate route names: first vs last registered (found on the trial) — D-S4-5

| | |
|---|---|
| Found | `RouteCollection::addLookups()` / `refreshNameLookups()` / `refreshActionLookups()` in L12 keep the **first** route for a duplicated name or controller action (`inNameLookup()`, `inActionLookup()`); L11 kept the **last** |
| Duplicated names (9) | `sr::`, `sr-latn::`, `en::` (POST login and POST password reset, both get the bare group name) → L11 `route()` = password reset, L12 = login; `*::biography` and `*::index-biographies` (Biographies registers the same names for its 4 pages: 3 teacher programmes + alumni) → L11 = alumni, L12 = fine-arts programme |
| Effect today | none visible: no code calls `route()` with these names directly; `Biography::uri()` builds its URL without `route()`; the golden master is identical without this fix. Possible through `Presenter.php:221` body links to biographies (`{!! biography:id !!}`) and any future use. `action()` / `getByAction()` are not used |
| Proposed fix (no `Modules/` route file, no view) | `app/Routing/RouteCollection.php`: `Illuminate\Routing\RouteCollection` with `inNameLookup()` and `inActionLookup()` returning `false` (= L11's "last one wins"); `AppServiceProvider::register()` moves the router onto it (`$router->setRoutes()`, copying any routes already registered). These two methods exist only in L12's `RouteCollection` (searched) |
| Verified on the trial | `route()` for all 9 names identical to L11; 278 routes, suite green, golden master identical |
| Alternative | give the duplicated routes unique names in `Modules/Biographies` / `typicms/users` route providers — a module / route-file change, not needed |
| Test first (4t) | `Boot/RouteNameTest`: `Route::getRoutes()->getByName('sr::')->uri()` = the password-reset route (last registered), and the same for the biography names if the BaselineSeeder has the pages (else only `sr::` / `sr-latn::` / `en::`). Green on L11, fails on L12 without the fix |

### 3.6 Paginator JSON: new `page` key in `links` (found by the golden master) — D-S4-6

| | |
|---|---|
| Found | 20 paginated admin API lists differ on the trial: every entry of `links` gets `"page": 1` / `null` (`{"url":…,"label":"1","page":1,"active":true}`); everything else identical |
| Effect | additive JSON key; the Vue admin does not read `links` (searched `resources/js`) |
| Proposed fix (no Vue change) | `app/Pagination/LengthAwarePaginator.php` (subclass, `linkCollection()` without `page`); `AppServiceProvider::register()` binds it — L12 builds paginators through `Container::makeWith(LengthAwarePaginator::class, …)`, so every `paginate()` uses it. Key order stays `url`, `label`, `active` |
| Verified on the trial | all 20 API lists byte-identical to `before-step-4` |
| Alternative | accept the additive key (API contract change, harmless for the current Vue) |
| Test first (4t) | `Api/PaginationLinksTest`: a paginated admin list → each `links` entry has exactly the keys `url`, `label`, `active`. Green on L11, fails on L12 without the fix |

### 3.7 Auth: remember-me cookie checks the password hash (L12) — D-S4-8

L12's `SessionGuard` gets `hashKey: app.key`; a remember-me cookie is accepted only if its password part matches the user's current password (`hash_equals(hmac(password), cookie)` **or** the raw hash L11 cookies contain). So **existing remember-me cookies stay valid after the deploy**; the one change: a remember-me cookie issued before a **password change** no longer logs the user in (L11 kept accepting it until `remember_token` changed — password resets already rotate it; an admin changing a user's password does not). Security hardening, not reachable by the suite / golden master; pinning it would mean overriding `SessionGuard`. `AuthenticateSession` is not used (commented out in the Kernel). Recommendation: accept.

### 3.8 R22: `App\Filesystem\AwsS3V3Adapter` and the files layer on L12

- `Illuminate\Filesystem\AwsS3V3Adapter` changed (9 lines): the constructor accepts any `League\Flysystem\FilesystemAdapter` (wider type) and sets `$config['directory_separator'] = '/'`. Our subclass calls the parent constructor with the same four arguments (`AppServiceProvider`) and only overrides `url($path)` → **compatible, no change**. `/` is already the separator on Linux.
- `FilesystemManager`: `enum_value()` for disk names, scoped-disk prefix / `throw` inheritance (no scoped disks here), `read-only` check fixed — no effect. `formatS3Config()` unchanged since L11.
- `LocalFilesystemAdapter`: new `temporaryUploadUrl()`; `temporaryUrl()` still uses our D7 callback first.
- → MinIO run `step-4` at the end of the step (O1); `Api/FilesS3UrlTest` green on the trial.

### 3.9 genealabs/laravel-model-caching: 12.0.4 or 13.x (R11) — D-S4-1

| Version | Allows | vs installed 11.0.1 (= 0.13.9) |
|---|---|---|
| **12.0.4** (recommended) | L10–L12 | 111 changed lines: the cache-key fixes (subquery bindings counted per placeholder, eager-load constraint closures hashed into the key, `DateTimeInterface` / int / bool values as strings, `strict_types`), `hasGlobalMacro` macros in the key, `__get('query')`, **`diffInSeconds(…, true)` (Carbon 3)**; switches to `mikebronner/laravel-pivot-events` (same code) |
| 13.1.11 / 13.2.0 | L12–L13 (13.2.0: PHP ≥ 8.3) | rewrite: 27 files, 3,085 changed lines vs 12.0.4, new classes, new config keys `ttl`, `fallback-to-database` |

**Cache keys change with 12.0.4** (those fixes are key changes; they make queries that differed only in an eager-load constraint or a subquery binding use separate keys — i.e. they fix potential stale results, they do not change correct results) → **`cache:clear` on deploy is mandatory** (O6). Step 5 (L13) needs 13.x anyway (12.0.4 allows up to L12); decide there with the same diff.

### 3.10 Golden master served by the L12 trial

Trial (L12.70.2, Carbon 3.15.1, PHP 8.4.27, dev DB `akademija` read-only, same uploads, captured inside the trial container after a warm-up run):

| State | Result vs `before-step-4` |
|---|---|
| L12, no compat | 344 same, **85 DIFF: 64 new 500s** (15 `@context`, 49 `route(…, '')`; with the known `/api/news/1019/files` that makes 65 × 500), 20 API lists (`page` key), 1 capture artefact |
| + `@context` + `Base::uri()` | 408 same, 21 DIFF (20 API lists + 1 artefact) |
| **+ route lookups + paginator (all fixes)** | **428 same, 1 DIFF**: `/likovne-umjetnosti/grafichki-dizaјn` `og:url` — the **capture artefact** known from step 3 (Linux curl percent-encodes the Cyrillic `ј`, the Windows host does not; identical for the same request bytes); the real `after-step-4f` capture runs from the host |

Status codes with all fixes: identical (414 / 14 / 1). Log: only the known `/api/news/1019/files` error.

### 3.11 Other L12 changes checked

| L12 change | Here | Effect |
|---|---|---|
| Config merging: new keys only (`cache.stores.session/failover`, `mail.mailers.*.retry_after`, `queue.connections.deferred/failover`) | dump L11 vs L12 | **no existing value changed**; routes 278 / 278 identical, middleware / groups / priority / aliases identical, events −1 framework listener (debug error-page query recorder), providers identical |
| `image` validation rule rejects SVG by default | no `image` rule in the project (Files uses `mimes:…svg…`, unaffected) | — |
| Container uses default values of nullable class-typed parameters | no such constructors in our classes | — |
| Local disk default root `storage/app/private` | `config/filesystems.php` defines `local` → unchanged | — |
| `DatabaseTokenRepository` expiry in seconds | no custom token repository | — |
| `mergeIfMissing` nested, UUIDv7, `Schema` changes | not used / migrations never run | — |
| Translator (enum replacements, unique locales, path checks), `MessageSelector` (inline conditions must be numeric), `FileLoader::loadPaths()` (merges several paths) | Translator patch (`localeForChoice`) and `MixedLoader` (one path) | still compatible; `ChoiceTest` and `Translations/*` green |
| `HandleExceptions::flushState()` | `tests/bootstrap.php` | exists; 0 risky |
| `Handler`, `AuthManager` refactors | `Handler::invalidJson()` override | 422 message unchanged (`ValidationMessageTest`) |
| New Blade directives `@hasStack`, `@includeIsolated` | not used as text anywhere | — |
| Methods new in L12 on Model / Builder / Collection / Request / FormRequest / providers / Command / Pivot / BelongsToMany (170) | none of these names defined in our classes | no override |
| Class load (394 classes incl. the new compat classes) | trial | 364 clean, 29 only the known PHP 8.4 implicit-nullable deprecations, 1 = the known `Setting` artefact |
| Runtime deprecations while serving 429 URLs | trial log | same types as step 3 (PHP 8.1 null-argument, implicit nullable); nothing new from L12 / Carbon 3 |
| Excel exports | harness | identical L11 / L12 — still broken on both (known issue, O8, D-S3-13) |

## 4. What in UPGRADE_PLAN.md is wrong or outdated

1. **§1.1 L12 column genealabs "12.x / 13.x":** 12.0.4 recommended (D-S4-1); laravel/framework ^12.0 locks 12.70.2; `symfony/clock` (new, transitive) on 7.4; prompts 0.3.
2. **§2.1 / §0 #2 "L13 still supports the L8 skeleton" holds for L12** (routes, middleware, config identical) — but L12 changes framework *behaviour* the skeleton cannot hide: Blade `@context`, empty route parameters, duplicate route-name resolution, paginator `links`. New risk rows (R25–R28).
3. **§3.4:** new pinned-core patches (`Base::uri()` empty slug, `formatLocalized` compat implemented as `Dates::formatLocalized()`); `app/` compat classes (`@context`, `RouteCollection`, `LengthAwarePaginator`).
4. **§8 step 4 row:** add the four L12 compat items and the 5 test-first tests; "Carbon 3 (required)" confirmed: no Carbon 3 output change beyond `formatLocalized`.
5. **R5:** `formatLocalized` is not reached by any view today (modules override `dateLocalized`); the compat is defensive and byte-identical.
6. **O6:** `cache:clear` mandatory again (genealabs 12.0.4 keys).

## 5. Proposed commit order (small, reviewable; this time every prep commit is green on L11)

| # | Commit | Green? |
|---|---|---|
| **4t** | the 5 test-first tests (§3.2–§3.6): `Presenters/LocalizedDatesTest`, `Views/JsonLdContextTest`, `Models/UriEmptySlugTest`, `Boot/RouteNameTest`, `Api/PaginationLinksTest` — written and green on L11 + Carbon 2. Test DB only (`akademija_testing`), transactions rolled back | **yes** (L11) |
| **4a** | `AppServiceProvider`: literal `@context` directive (L11 has no `@context`, so the custom directive returns the same text) | **yes** (L11): suite + golden master identical |
| **4b** | pinned core `Base::uri()`: empty slug → L11 URL; `PATCHES.md` | **yes** (L11: identical output, the branch reproduces L11's `route()`): suite + golden master |
| **4c** | `app/Routing/RouteCollection.php` + `AppServiceProvider::register()` | **yes** (L11 has no `inNameLookup()` / `inActionLookup()`, the overrides are never called): suite + golden master |
| **4d** | `app/Pagination/LengthAwarePaginator.php` + binding | **yes** (L11 has no `page` key, nothing removed): suite + golden master |
| **4e** | pinned core `Dates::formatLocalized()` + `Presenter` / `Dates::concat()` use it; `PATCHES.md` | **yes** (L11: same algorithm as Carbon 2, identical output): suite + golden master |
| **4f** | composer.json + composer.lock (§3.1 list, `symfony/clock` 7.4, no `-W`) | **yes**: full suite (all 5 new tests), golden master `after-step-4f` vs `before-step-4`, routes / config dump, CORS, class load |
| — | **MinIO run `tools/s3-test/run.sh step-4`** (announced first; resets **only `akademija_s3test`**, never `akademija`; artisan and checks as `www-data`); results into the report | — |
| 4g | documentation: UPGRADE_PLAN.md (§1, §3.4, §8, §9 R25–R28, O1/O3/O6, new §17), tests/README.md, `tools/s3-test/S3_TEST_REPORT.md` (step 4 column), STEP4_PLAN.md | — |

Test-first check for each of 4a–4e: the corresponding 4t test must **fail** on L12 with that commit reverted in the working tree (checked after 4f, not committed).

## 6. Production (needed before deploying step 4)

| Item | |
|---|---|
| PHP | **8.4** (unchanged from step 3), same extensions (STEP3_PLAN §6) |
| Composer | only `composer install` from the committed lock, Composer ≥ 2.7 (dev: 2.2.30, same workarounds as step 3) |
| Deploy steps | as the web server user (not root): `composer install --no-dev --optimize-autoloader`; `php artisan optimize:clear`; **`php artisan cache:clear` mandatory** (genealabs 12.0.4 cache keys; also drops cache entries serialized with Carbon 2 objects); open a few pages before traffic (cold compiled-view cache). Do not run `route:cache` / `view:cache` (fail today). No migrations, no DB change |
| Users | remember-me cookies stay valid; after a password change, remember-me cookies issued before it stop working (§3.7) |
| Open items | O1 (MinIO `step-4` + AWS test bucket), O2, O3 (L12 on S3), O5, O6, O7 (Mailgun), O8 (manual checks; the Excel known issue) |

## 7. Risks

| # | Risk | Mitigation |
|---|---|---|
| R-S4-1 | Blade `@context` (and any future framework directive) vs literal `@word` text in views | custom `@context` directive (D-S4-3), `JsonLdContextTest`; in step 5 re-list new directives (same script) |
| R-S4-2 | `route()` rejects empty parameters | `Base::uri()` patch (D-S4-4), `UriEmptySlugTest`; other call sites only see non-empty slugs with today's data (R-S4-4) |
| R-S4-3 | duplicate route names / actions resolve to the first route | `RouteCollection` compat (D-S4-5), `RouteNameTest`; re-check `RouteCollection` in L13 |
| R-S4-4 | a published item without a slug in its own locale (or a tag without slug) would make `Presenter.php:221`, `_items-navigator` or `Tag::uri()` throw on L12 (L11 rendered a list link) | none in the data today (checked); admin validation requires a slug with a title; a global URL-generator compat is possible if it is ever needed |
| R-S4-5 | paginator JSON `page` key | `LengthAwarePaginator` compat (D-S4-6), `PaginationLinksTest` |
| R-S4-6 | Carbon 3: `formatLocalized` removed; other Carbon 3 changes | compat (D-S4-7), `LocalizedDatesTest`; no other Carbon 3 API used (§3.2) |
| R-S4-7 | genealabs 12.0.4 cache-key changes | `cache:clear` on deploy (O6); results only change where 11.0.1 could share a key between different queries |
| R-S4-8 | remember-me after a password change | accepted (D-S4-8) |
| R-S4-9 | the compat classes depend on L12 internals (`inNameLookup`, `linkCollection`, `formatParameters` behaviour, custom-directive precedence) | each pinned by its 4t test; re-check all in step 5 (L13) |

## 8. Open questions / decisions for you

| ID | Question | Recommendation |
|---|---|---|
| **D-S4-1** | genealabs: 12.0.4 (L10–L12, 111-line diff, key fixes) / 13.1.11 or 13.2.0 (rewrite, L12–L13) | **12.0.4**; 13.x in step 5 with its own diff |
| **D-S4-2** | `symfony/clock` (new, via Carbon 3): keep on 7.4 LTS with a temporary constraint (as D-S3-5) / Composer's choice v8.1.9 | **7.4** |
| **D-S4-3** | Blade `@context`: (a) literal custom directive in `AppServiceProvider` + `JsonLdContextTest` / (b) change the 20 views to `@@context` | **(a)** (no view change) |
| **D-S4-4** | empty-slug `route()`: (a) `Base::uri()` patch in the pinned core + `UriEmptySlugTest` / (b) global URL-generator compat | **(a)** |
| **D-S4-5** | duplicate route names: (a) `App\Routing\RouteCollection` (last wins, as L8–L11) + `RouteNameTest` / (b) accept first-wins (no visible effect today) / (c) rename the routes in the modules | **(a)** (keeps L11 behaviour, no module change) |
| **D-S4-6** | paginator `links[].page`: (a) `App\Pagination\LengthAwarePaginator` without `page` + `PaginationLinksTest` / (b) accept the additive key | **(a)** (API JSON identical) |
| **D-S4-7** | `formatLocalized` compat as `Dates::formatLocalized()` in the pinned core (Carbon 2 algorithm) + `LocalizedDatesTest` | **yes** |
| **D-S4-8** | remember-me cookie vs password change (L12 hardening) | **accept** |
| **D-S4-9** | laravel/framework: lock 12.70.2 (released 2026-10-27) or the previous 12.70.1 / 12.69.3 | **12.70.2** (latest; everything verified on it) |
| **D-S4-10** | commit order §5 (4t, then 4a–4e each green on L11, then 4f) | **yes** |

No item needs a change to `Modules/`, the views, Vue / frontend, `package.json`, a test assertion of an existing test, or the database.

Housekeeping: the trial container `s3trial` (Laravel 12 with all trial-only patches: `@context`, `Base::uri()`, `RouteCollection`, paginator, `formatLocalized`; `no-api`; deprecation log channel; Apache on 8080) and image `akademija-step3-trial:php84` are kept for the implementation; remove with `docker rm -f s3trial && docker rmi akademija-step3-trial:php84`. Trial captures `trial-step-4`, `trial-step-4b`, `trial-step-4c` and their compare folders are in the git-ignored `storage/golden-master/`.
