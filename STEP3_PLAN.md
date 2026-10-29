# STEP 3 PLAN — Laravel 10.50 → 11.x, PHP 8.2 → 8.4

**Analysis only (2026-10-29). No code, config, test or database file was changed.**
Branch `upgrade/step-3` created from `development` (`c022f1d`, = `origin/development`, step 2 merged). Not committed, not pushed.

Everything below was checked against real code, not only read: a **throwaway trial** (container `s3trial` from a PHP 8.4 copy of `docker/php/Dockerfile`, code = `git archive HEAD`, own vendor; the working tree, the `vendor` volume and the dev containers were not touched) with
- the current lock on **PHP 8.4.27 + Laravel 10.50.3** (suite),
- a **trial resolution and install of Laravel 11.57.1** (explicit package list, no `-W`) and on it: the full suite, a **class load of all 391 project / pinned-copy classes**, a **PHP 8.4 lint of 638 files**, a **dump-and-diff of the booted app on L10 vs L11** (every config value, all 278 routes with their middleware, HTTP middleware / groups / priority / aliases, event listeners, loaded providers), a **method-collision check** (methods new in L11 framework base classes vs methods our classes define), the **golden master (429 URLs) served by the L11 trial** and compared with `before-step-3`, the module **Excel exports** on L10 vs L11, `artisan` cache commands;
- source diffs of the candidate packages (genealabs 0.13.9 / 11.0.1 / 12.0.4 / 13.1.10, pivot-events 10.0.1 / 11.0.0) and of the framework classes our code depends on (Translator, MessageSelector, FilesystemManager, AwsS3V3Adapter, BladeCompiler).

## 1. Baseline (current state: Laravel 10.50.3, PHP 8.2.35)

| Check | Result |
|---|---|
| Full suite (Docker) | **73 tests, 552 assertions, OK** |
| Golden master `before-step-3` (429 URLs, 2026-10-29) | 414 × 200, 14 × 404, 1 × 500 (`/api/news/1019/files`, the same 500 as in every capture) |
| `before-step-3` vs `after-step-2` (same code, 2026-10-22) | 421 identical; **8 differ by data only**: announcement **id 503** ("srpski" / "latinica") was created in the dev DB `akademija` on **2026-10-29 10:03 UTC by user 1 through the admin** (history row `created`), so the noticeboard lists (4 URLs), `/sitemap.xml`, `/api/announcements`, `/api/tags` (a tag use +1) and `/api/history` changed. Not written by the upgrade tooling (tests use `akademija_testing`, MinIO `akademija_s3test`). **Reference for step 3 is `before-step-3`** |

## 2. Summary

- **Required:** Dockerfile PHP 8.4; composer (Laravel 11 + Symfony 7.4 + the packages that block it); `hashing.rehash_on_login = false`; **one framework-behaviour pin found by the golden master: the plural form of `@choice` (Translator patch in the pinned `typicms/translations`, §3.5)**; technical test changes for PHPUnit 11 (`phpunit.xml` schema, one line in `tests/bootstrap.php`).
- **Not required (verified):** the Laravel 11 slim skeleton (the old skeleton gives identical routes, middleware, priority and config values, §3.3); the core `formatLocalized` patch (Carbon stays 2.73.0, L11 allows it → moves to step 4); any change in `Modules/`, views, Vue/JS/CSS, `package.json`, routes, database, `App\Filesystem\AwsS3V3Adapter`, the D7 local-disk callback, the `typicms/users` / `typicms/roles` patches; ultrono, typidesign, tinker, debugbar, ignition, laravel/ui, Permission, eloquent-sortable, Excel, Croppa, presenter, utilities, honeypot bumps.
- **CLAUDE.md check — modules, views, Vue, frontend, `package.json`, look and behaviour:** **nothing in step 3 touches them.** No file in `Modules/`, `resources/`, `public/`, `routes/`, `database/` or the JS/CSS build changes. Visible output stays identical **only with the Translator patch (D-S3-8)**; without it the search result headings change (§3.5). The only remaining output difference is the HTTP header `Content-Type: text/html; charset=UTF-8` → `charset=utf-8` (Symfony 7, not visible, D-S3-9).
- **Golden master on the L11 trial (with the Translator patch):** of 429 URLs, **0 body differences caused by the upgrade**; 402 HTML responses differ only in the charset case of the `Content-Type` header; 1 URL differs only because the trial capture ran from Linux instead of the Windows host (§3.13).
- **Decisions for you** (§8): Symfony line, genealabs line, PHPUnit 11, PHP constraint, Translator patch, charset header, Carbon, deprecation fixes, commit order.

## 3. Items

### 3.1 Dockerfile → PHP 8.4

| | |
|---|---|
| Now | `FROM php:8.2-apache-bookworm` |
| Change | `FROM php:8.4-apache-bookworm` (comment D-S1-4 / D-S2-7 / D-S3-1). Extension list unchanged |
| Verified | image built: **PHP 8.4.27, Debian 12.15 (bookworm)**, all extensions present (same `php -m` list). ICU 72.1 (same as the 8.2 image), bundled PCRE2 10.44 (8.2 image: 10.40). Composer 2.2.30 (`composer:2.2`) works on PHP 8.4 (all trial resolutions ran with it) |
| On L10 | **suite green on PHP 8.4 + Laravel 10.50.3** (73 tests, 552 assertions; trial). So PHP 8.4 can go first on L10, as 2a did for 8.2 |
| PHP 8.4 / 8.3 changes checked | lint of 638 files (`app/`, `Modules/`, `config/`, `routes/`, `database/`, `tests/`, `bootstrap/`, `packages/`): **0 errors**; compile-time deprecations only "implicitly nullable parameter" (14 sites in 7 files: pinned core `Base`, nestablecollection, translatable 4.6, translatable-bootforms, MixedLoader, genealabs, and `Modules/Pages/Observers/UriObserver.php`), logged to the `null` channel, fatal only in PHP 9 (D-S3-10). Runtime deprecations seen while serving the 429 URLs are all PHP 8.1 "passing null to a non-nullable internal parameter" (`Menu.php:92`, `FilesystemAdapter::path` `ltrim`, sitemap `ucfirst`, form `htmlentities`) — present on 8.1/8.2 today as well, logged to `null`. **`round()`** (PHP 8.4 removed the pre-rounding): the only call is `Files\ModulePresenter::filesize()`; on the 6,453 real file sizes 14 results differ at precision 0 (e.g. 72192 B: `71` on 8.2, `70` on 8.4) — but **`filesize()` is never called** (no view, no PHP caller; the Vue admin formats the raw `filesize` itself), so no output changes; recorded as a latent difference. `password_hash()` default cost 10 → 12 in PHP 8.4: Laravel passes the cost from `config/hashing.php` (10), no effect. No `E_STRICT`, `array_sum/array_product`, `number_format` use in our code |
| Public / module impact | none |
| Risk | low |
| Confirmed by | full suite + golden master on L10 + PHP 8.4 (commit 3a) |

### 3.2 composer.json (L11)

Trial resolution in a throwaway copy, `composer update` with an explicit list, **no `-W`** (with `-W` / `-w` Composer would also be free to lift Carbon to 3, which L11 allows):

| Package | Now (locked) | Target | Why / note |
|---|---|---|---|
| php | ^8.2 | **^8.4** (D-S3-2) or keep ^8.2 | L11 needs ^8.2; ^8.4 makes Composer refuse an install on an older production PHP |
| laravel/framework | ^10.48 (10.50.3) | **^11.0** → locks **v11.57.1** (2026-10-27, latest 11.x) | requires Symfony ^7.0.3 (http-foundation ^7.2), termwind ^2, prompts ^0.1.18\|^0.2\|^0.3, Carbon ^2.72.6\|^3.8.4 |
| genealabs/laravel-model-caching | ^0.13.9 | **^11.0.1** (11.0.1) | **`src/` and `config/` byte-identical to 0.13.9**, only the constraints add L11 (D-S3-3, §3.7) |
| genealabs/laravel-pivot-events | 10.0.1 (transitive) | 11.0.0 | `src/` byte-identical to 10.0.1, only metadata/constraints |
| symfony/http-client, symfony/mailgun-mailer | ^6.4 | **^7.4** (7.4.22 / 7.4.18) | L11 requires Symfony 7 Mailer; Mailgun bridge 7.4 (O7) |
| nunomaduro/collision (dev) | ^7.0 (7.12.0) | **^8.0** → 8.9.5 | 7.x conflicts with L11. **Every 8.x release conflicts with PHPUnit < 10.5.1** (8.9 needs ≥ 11.5.50) → PHPUnit must move (D-S3-4) |
| phpunit/phpunit (dev) | ^9.3 (9.6.38) | **^11.5.50** → 11.5.57 | plan (UPGRADE_PLAN §11) and needed by collision 8.9; 11.5 also covers L12/L13. Test-side changes: §3.6 |
| Transitive, Symfony | 6.4.x | **7.4 LTS** (console, error-handler, finder, http-foundation, http-kernel, mailer, mime, process, routing, uid, var-dumper, **string, css-selector, event-dispatcher**) | D-S3-5. Composer's free choice puts `string`, `css-selector`, `event-dispatcher` on **8.1**; keeping those three on 6.4 pins `console` / `http-kernel` to **7.2.9 (end-of-life branch)**. Recommended: all on 7.4 via temporary `--no-update` constraints during the update, removed again before the commit (composer.json then contains only the rows of this table; lock validated) |
| symfony/translation | 6.4.44 | 6.4.49 (patch) | Carbon 2 does not allow translation 7 |
| Other transitive | | termwind 1.17 → 2.4.0, new `league/uri` 7.9.1 + `uri-interfaces`, new `symfony/polyfill-php85`; dev: sebastian/* and phpunit/* majors, new `staabm/side-effects-detector`, removed `doctrine/instantiator`, `sebastian/resource-operations` | |
| **Unchanged and verified for L11** | | nesbot/carbon **2.73.0**, laravel/prompts 0.1.25, serializable-closure 1.3.7, laravel/ui 4.6.3, spatie/laravel-permission 6.25.0, spatie/eloquent-sortable 4.5.2 (`^9.31…^12`), maatwebsite/excel 3.1.70 (PhpSpreadsheet 1.30.7), bkwld/croppa 8.0.1, laracasts/presenter 0.2.9, laracasts/utilities 3.2.5, msurguy/honeypot 1.5.0, **ultrono/laravel-sitemap 9.5.0** (allows ^11\|^12), **typidesign 1.0.4** (no framework constraint; `translations:add/remove` run on L11), **laravel/tinker 2.11.1** (allows ^12), barryvdh/laravel-debugbar 3.16.0, spatie/laravel-ignition 2.9.1 (+ ignition 1.16.0; 2.10+ needs symfony/console ^7.4 and is not needed), league/flysystem-aws-s3-v3 3.32.0 + aws/aws-sdk-php **3.337.3 (pin kept, O1)**, Sidebar VCS fork `09d438e`, all `typicms/*` / translatable / nestablecollection / QB 6.4.4 copies (constraints already ^11–^13) | |

Trial lock result (recommended variant): **45 updates, 4 installs, 2 removals** (prod: framework, genealabs ×2, termwind, 19 Symfony packages incl. `translation` 6.4 and two polyfills at patch level; installs `league/uri` ×2, `polyfill-php85`, dev `side-effects-detector`; the rest dev). Command (for 3e), after `composer require --no-update` of the rows above and temporarily `symfony/string:^7.4 symfony/css-selector:^7.4 symfony/event-dispatcher:^7.4`:
`composer update laravel/framework genealabs/laravel-model-caching genealabs/laravel-pivot-events nunomaduro/collision nunomaduro/termwind "symfony/*" "phpunit/*" "sebastian/*"`
then the three temporary constraints are removed again and the lock hash refreshed (`composer update --lock`).

Public / module impact: none. Risk: medium (framework major), covered by suite + golden master + MinIO run.

### 3.3 Laravel 11 skeleton: what is mandatory, what is optional

Laravel 11's new application structure (`bootstrap/app.php` with `Application::configure()`, `bootstrap/providers.php`, no HTTP/Console Kernel, no `app/Exceptions/Handler.php`, slimmer `config/`) is **optional**. Verified on the L11 trial with the unchanged Laravel 8-style skeleton (`App\Http\Kernel`, `App\Console\Kernel`, `App\Exceptions\Handler`, `RouteServiceProvider::map()`, `config/app.php` provider list, old `bootstrap/app.php`, `public/index.php`, `artisan`), booted app dumped on L10 and L11 and diffed:

| Area | L10 vs L11 |
|---|---|
| Routes | **278 / 278, identical** (method, URI, action, middleware, name) |
| HTTP middleware, groups, priority, aliases (`$routeMiddleware`) | **identical** |
| Config | **no existing value changed**. Keys added by L11's framework config merging (only missing keys are added): `app.previous_keys` `[]`, `app.maintenance.*`, `app.frontend_url`, `concurrency.default`, `broadcasting.connections.reverb/ably`, `cache.stores.octane`, `database.connections.mariadb` (unused, `DB_CONNECTION=mysql`), **`hashing.rehash_on_login = true`** (§3.4), `logging.deprecations` (channel `null`, as today), `mail.mailers.resend/failover/roundrobin`, `queue.batching.*`, `services.resend/slack`, `session.partitioned = false`. None of them is read by our code; apart from `rehash_on_login` none changes behaviour |
| Event listeners | 188 → 194: +3 framework listeners (log context on `CommandFinished` / `JobAttempted`, the new debug error page's query recorder on `QueryExecuted`; development error page only). **No duplicate `Registered` → `SendEmailVerificationNotification`** listener (L11 only adds it when it is not registered) |
| Providers | +1 framework provider (`Illuminate\Log\Context\ContextServiceProvider`) |
| Method collisions | 75 method names are new in L11 on `Model`, Eloquent/Support `Collection`, `Builder`, `Controller`, `FormRequest`, `Request`, service providers, `Handler`, `Command`, `Pivot`, `BelongsToMany`. Our same-named methods (`uri()` on models / the Pages controller, `data()`, `before()`, `after()` in the TypiCMS form builder) are on classes that do not extend the class where L11 added them → **no override**. No model defines `casts()` |
| `artisan` | `about`, `route:list`, `config:cache`, `event:cache`, `optimize:clear`, `schedule:list`, `translations:*` work. **`route:cache` and `view:cache` fail on L11 and identically on L10** (duplicate route name `sr::`; missing `Modules/Files/resources/views`) — existing, deploy must not use them (§6) |

**Mandatory in step 3:** nothing in the skeleton. **Optional, not recommended (D-S3-11):** migrating to `bootstrap/app.php` / `bootstrap/providers.php`, `$middlewareAliases`, deleting the Kernels / Handler, slimming `config/`. It would reorder providers (the Pages catch-all route must stay last), change exception rendering and touch many files without a technical need.

### 3.4 `hashing.rehash_on_login = false`

L11 adds `rehash_on_login` (default `true`): at login, if the stored hash's algorithm/cost differs from the configured one, Laravel **writes a new `users.password`**. Today all 5 users (production dump) have `$2y$10$` = `config/hashing.php` `rounds` 10, so nothing would be rewritten with the current settings — but a production `.env` `BCRYPT_ROUNDS` ≠ 10 would start writing the users table at every login. Pin `'rehash_on_login' => false` in `config/hashing.php` (planned in UPGRADE_PLAN §2.2). L10 ignores the key, so the commit is green on L10. Public / module impact: none. Confirmed by `Auth/*`.

### 3.5 Translator: plural form of `@choice` (found by the golden master) — D-S3-8

| | |
|---|---|
| Found | golden master on the L11 trial: the search results headings change: `/search-results?search=akademija` **`66 страница` → `66 странице`**, `/sr-latn/search-results?search=akademija` **`84 stranica` → `84 stranice`** |
| Where | `resources/views/vendor/search/public/index.blade.php:21` (published copy of `typicms/search`): `{{ $count }} @choice(__($result['module']), $count)`. `__('pages')` = `"страница\|странице"` (sr) / `"stranica\|stranice"` (sr-latn) from `resources/lang/sr.json`, `sr-latn.json`. It is the only `@choice` / `trans_choice` in the project |
| Cause | `Illuminate\Translation\Translator::localeForChoice()` changed in L11: L10 `return $locale ?: $this->locale ?: $this->fallback;` → L11 `localeForChoice($key, $locale)`: the current locale only if **`$key` exists as a translation key** in it, otherwise the **fallback locale (`en`)**. The view passes the already translated string, which is never a key → **every Serbian count uses the English plural rule** (`n == 1 ? 0 : 1`). `MessageSelector` is identical in L10 and L11 (diffed) |
| Effect without a fix | sr: 5, 11, 66, 101… → `странице` instead of `страница`; sr-latn: every count ≠ 1 → `stranice` instead of `stranica`. Visible on the public search page |
| Proposed fix (no view / module change) | pinned `typicms/translations` copy (where the step 2 `MixedLoader` patch lives): new `src/Translator.php` = `Illuminate\Translation\Translator` with the L10 `localeForChoice()`; `src/Providers/TranslationServiceProvider.php` (already replaces Laravel's provider, which is commented out in `config/app.php`) overrides `register()` to build that class — the closure is identical to Laravel's (L10 = L11 `register()`, diffed) except for the class name. `PATCHES.md` entry |
| Verified on the trial | with the patch the plural table equals L10 for every count tested (1, 2, 4, 5, 11, 21, 22, 66, 84, 101 in sr and sr-latn); both search pages identical to `before-step-3`; suite green |
| Test first | new test (3t): `trans_choice(__('pages'), n)` / `@choice` for sr and sr-latn equals today's output; green on L10, **fails on L11 without the patch** |
| Note | today's output is itself grammatically wrong in places (`84 stranica`, `5 странице`…) because the strings have 2 forms and the `sr-latn` locale code has no Laravel plural rule. Pinned as is (CLAUDE.md: same behaviour); fixing the wording is a Stage-2 content change |
| Public / module impact | none with the patch. Risk: low |

### 3.6 PHPUnit 11 and the test suite (technical test changes, UPGRADE_PLAN §11)

| File | Change | Why / verified |
|---|---|---|
| `phpunit.xml` | output of `phpunit --migrate-configuration`: `<coverage processUncoveredFiles>` → `<source>`, `cacheDirectory=".phpunit.cache"` (already in `.gitignore`), re-indented. **All `<php>` `<server>` values identical** | PHPUnit 11 reports the old schema as a deprecation |
| `tests/bootstrap.php` | **one line** after `$app->flush();`: `\Illuminate\Foundation\Bootstrap\HandleExceptions::flushState();` | the bootstrap boots an app to rebuild `akademija_testing` *before* PHPUnit starts, which installs Laravel's error/exception handlers globally; L11's per-test teardown removes them and PHPUnit 11 then marks **72 of 73 tests risky** ("removed error handlers other than its own"). With the line: **OK (73 tests, 552 assertions), 0 risky, 0 deprecations**. `flushState()` does not exist in L10 → same commit group as the composer update |
| tests | **no test code changes** (no doc-block annotations, no PHPUnit APIs removed in 10/11 are used) | |

### 3.7 genealabs/laravel-model-caching (D-S3-3)

| Version | Allows | vs installed 0.13.9 |
|---|---|---|
| **11.0.1** (recommended) | L10–L11 | **`src/` and `config/` identical**; composer constraints only. Same cache keys → no cache-format change in step 3 |
| 12.0.4 | L10–L12 | 111 changed lines (the eager-load / subquery / value cache-key fixes described in STEP2_PLAN §3.8; switches to `mikebronner/laravel-pivot-events`) |
| 13.1.10 (last with L11) | L11–L13 | rewrite: 2,331 changed lines, new classes (`CachedHasManyThrough`, `CachedMorphToMany`, `CachedQueryBuilder`, …) |

Step 4 (L12) needs 12.x or 13.x; decide there with the same diff (R11). Package is abandoned in favour of `mike-bronner/laravel-model-caching`.

### 3.8 Symfony 7.4 LTS (D-S3-5)

L11 requires Symfony 7 (console, http-kernel, routing, mailer, …). Recommended: **every Symfony component on 7.4 LTS**, `symfony/translation` stays 6.4 (Carbon 2). Verified effects: **`Content-Type: text/html; charset=UTF-8` → `charset=utf-8`** on every HTML response (Symfony 7 `Response::prepare()` default `'utf-8'`; JSON responses unchanged) — D-S3-9. Mail: Symfony Mailer + Mailgun bridge 7.4, same `services.mailgun` keys; tests use the `array` mailer → O7 (real send on a non-production Mailgun domain) stays open.

### 3.9 Carbon stays 2 (D-S3-6)

L11 allows `nesbot/carbon ^2.72.6|^3.8.4`; the explicit update list keeps **2.73.0**. So `Carbon::formatLocalized()` still exists and the planned core `formatLocalized` compat patch (UPGRADE_PLAN §3.4, R5) is **not needed in step 3**; it moves to step 4 (L12 requires Carbon 3). `DateCastsTest`, `NewsModelTest`, `ClassscheduleTest` green on L11.

### 3.10 Files / S3 on L11

- `Illuminate\Filesystem\AwsS3V3Adapter`: **byte-identical** 10.50.3 → 11.57.1 → `App\Filesystem\AwsS3V3Adapter` needs no change (R22). `callCustomCreator()` passes the same arguments.
- `FilesystemManager::formatS3Config()`: new `credentials` provider handling (`ecs` / `instance`); our `s3` disk has no `credentials` key → the same `key`/`secret` path as L10. `createSftpDriver()` root default changed (SFTP only, unused).
- `createLocalDriver()` now returns `LocalFilesystemAdapter` with a disk name and optional `serve` signed URLs; our D7 `Storage::extend('local', …)` calls it without a name (name `local`, only used with `'serve' => true`, not configured). `temporaryUrl()` still uses our callback first → the 12 document pages are identical in the golden master. No `filesystems.disks.*` key is added by config merging.
- PHP 8.4 + AWS SDK 3.337.3 (pin kept) + L11 → **MinIO run `step-3` required** (O1, O3) after 3e.

### 3.11 Other L11 changes scanned (`app/`, `Modules/`, `packages/`, `config/`, `routes/`, `tests/`)

| L11 change | Found | Effect |
|---|---|---|
| `Authenticatable::getAuthPasswordName()` added to the contract | `typicms/users` User implements the contract with the framework trait | class loads on L11 (trait provides it) |
| Custom `UserProvider` (`rehashPasswordIfRequired`) | none | — |
| Per-second rate limiting | `RouteServiceProvider`: `Limit::perMinute(60)` for `api` | still supported, same limit; route middleware identical |
| `Model::casts()` method | not defined anywhere | — |
| Doctrine DBAL removal, column modification, float types, spatial types | migrations are never run | — |
| `$dates` / Carbon 3 / `diffIn*` floats | Carbon stays 2 | — |
| Framework config merging | §3.3 | `rehash_on_login` only |
| `Translator::choice()` | §3.5 | patch |
| Symfony 7 `Response` charset | §3.8 | header case only |
| Mail `Mailable` / Symfony Mailer 7 | `App\Mail\ContactMail` | suite (array mailer); O7 |
| Paginator JSON, model serialization, QB 6.4.4 append patch | — | golden master: all 26 `/api` URLs identical |
| Excel exports | `Modules/*/Exports/Export.php` (10 modules) | identical L10 / L11 in a harness; **but the real export request fails on both versions today** (known issue, §7) |

### 3.12 Class load and deprecations (PHP 8.4.27 + L11.57.1)

391 classes (`app/`, `Modules/`, `packages/*/*/src`), one process each: 361 without any message, 29 with only the "implicitly nullable" deprecations of §3.1 (they are reported by every class that uses `Base` / `HasTranslations`), 1 failure = `Settings\Models\Setting` (`Eloquent` alias exists only in a booted app; same on L8–L10; loads with 0 messages when booted). No new error compared with L10.

### 3.13 Golden master served by the L11 trial

Trial app (L11.57.1, PHP 8.4.27, Translator patch, dev DB `akademija` read-only, same uploads, same `.env`), captured inside the trial container with the unchanged `tools/golden-master/capture.php`, compared with `before-step-3`:

| Result | URLs |
|---|---|
| status codes | identical (414 × 200, 14 × 404, 1 × 500) |
| body identical | **428** |
| body differs | **1**: `/likovne-umjetnosti/grafichki-dizaјn` (slug with a Cyrillic `ј`): `og:url` `…dizaјn` vs `…diza%d1%98n`. **Capture artefact**: the Windows host's curl sends the `ј` raw, Linux curl percent-encodes it; for the same request bytes L10 and L11 give the identical `og:url` (checked with raw, `%D1%98` and `%d1%98` on both). The real `after-step-3` capture runs from the host like the baseline |
| header only | 402 HTML responses: `Content-Type` `charset=UTF-8` → `charset=utf-8` (§3.8) |
| without the Translator patch | additionally the 2 search pages (§3.5) |

Note: one trial capture right after `optimize:clear` (6 parallel requests, cold compiled-view cache) returned 2 truncated pages; the repeat with compiled views was clean. `BladeCompiler::compile()` (non-atomic `files->put()`) is identical in L10 and L11, so this is an existing cold-cache race, not an upgrade change (deploy note §6).

## 4. What in UPGRADE_PLAN.md is wrong or outdated

1. **§1.1 L11 column genealabs ^12.0:** 11.0.1 is the L11 line with code identical to 0.13.9; 12.x/13.x only in step 4.
2. **§1.1 ultrono ^10.0 at L11, typidesign ^4.0 at L11, tinker ^2.9 at L11:** not required — ultrono 9.5 allows ^11\|^12, typidesign 1.0.4 has no framework constraint, tinker 2.11.1 allows ^12.
3. **§1.1 symfony/mailgun-mailer + http-client ^7.x:** ^7.4 (LTS); **all Symfony on 7.4 LTS**, `symfony/translation` stays 6.4 with Carbon 2.
4. **§1.1 require-dev collision ^8.x:** every collision 8 release conflicts with PHPUnit < 10.5.1 (8.9: < 11.5.50) → PHPUnit 11.5 is required in step 3, not only "planned".
5. **§3.4 / R5 / §8 step 3 row `formatLocalized` compat:** not needed in step 3 (Carbon stays 2), moves to step 4.
6. **§3.4 / §9 new item:** `typicms/translations` Translator patch (L11 `localeForChoice`, §3.5); new risk row.
7. **§11:** `tests/bootstrap.php` `flushState()` line (new technical change) next to the `phpunit.xml` migration.
8. **§8 step 3 row:** update per §5 (PHP 8.4, L11.57.1, genealabs 11.0.1, collision 8.9, PHPUnit 11.5, Symfony 7.4, rehash pin, Translator patch; no ultrono/typidesign/tinker bump, no `formatLocalized`).
9. **O6:** `route:cache` / `view:cache` must not be used (fail on L10 and L11, existing).
10. **O5:** production PHP 8.4.

## 5. Proposed commit order (small, reviewable; only the marked commits must be green)

| # | Commit | Green? |
|---|---|---|
| **3t** | new `tests/Feature/Translations/ChoiceTest.php` (D-S3-8): `@choice` / `trans_choice(__('pages'), n)` for sr and sr-latn (counts 1, 2, 4, 5, 11, 21, 22, 66, 84, 101) equals today's output. Written and green on L10 | **yes** (L10) |
| **3a** | Dockerfile `php:8.4-apache-bookworm` (still L10.50.3) | **yes**: suite (trial-verified) + golden master `after-step-3a` identical to `before-step-3` |
| **3b** | `config/hashing.php` `'rehash_on_login' => false` (L10 ignores the key) | **yes**: suite + golden master identical |
| 3c | pinned `typicms/translations`: `src/Translator.php` + `TranslationServiceProvider::register()` + `PATCHES.md` | no (the L11 `localeForChoice($key, $locale)` signature is incompatible with L10) |
| 3d | `phpunit.xml` (`--migrate-configuration`) + the `tests/bootstrap.php` `flushState()` line | no (needs PHPUnit 11 / L11) |
| **3e** | composer.json + composer.lock (§3.2 list, Symfony 7.4, no `-W`) | **yes**: full suite, `ChoiceTest`, `DateCastsTest`, `AppendsTest`, `FilesS3UrlTest`, golden master `after-step-3` vs `before-step-3`, CORS, class load, deprecation log |
| — | MinIO run `tools/s3-test/run.sh step-3` (no commit; results into the report) | — |
| 3f | documentation: UPGRADE_PLAN.md (§1, §3.4, §8, §9, §11, O1/O3/O5/O6, new §16 step 3 record), tests/README.md, `tools/s3-test/S3_TEST_REPORT.md` (step 3 column) | — |

Test-first check (as in 1c-7a / 2t): `ChoiceTest` must **fail** on L11 without 3c — checked by temporarily reverting 3c in the working tree (not committed).

Expected golden master `after-step-3` (captured from the host like the baseline): identical bodies; only the `Content-Type` charset case on HTML responses (unless D-S3-9 b).

## 6. Production (needed before deploying step 3)

| Item | |
|---|---|
| PHP | **8.4** (tested 8.4.27), 64-bit. PHP 8.4 is supported until end of 2028 (security) |
| Extensions | the Dockerfile set: `pdo_mysql`, `mbstring`, `xml`, `bcmath`, `curl`, `zip`, `gd` (freetype + jpeg), `intl`, plus the PHP defaults the lock checks: `ctype`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `openssl`, `pcre`, `session`, `simplexml`, `tokenizer`, `xmlreader`, `xmlwriter`, `zlib` (`composer check-platform-reqs --no-dev`: all satisfied on the 8.4 image) |
| OS | Debian 12 (bookworm) as in Docker until O5 is confirmed |
| Composer | **Production: only `composer install` from the committed `composer.lock` (never `composer update`), with Composer 2.7 or newer** (the lock itself needs `composer-runtime-api` ^2.2). Dev uses Composer **2.2.30** (`COPY --from=composer:2.2` in the Dockerfile), which needed two workarounds to *resolve* the step 3 lock: (1) the Sidebar VCS repository only resolves with `"no-api": true` (anonymous GitHub API access fails, the SSH fallback has no key) and (2) `--with` takes only root packages, so the three Symfony 7.4 constraints were added temporarily; both were removed again before the commit, and because `composer update --lock` cannot run without (1), the lock's `content-hash` was written with Composer's own `Locker::getContentHash()` (`composer validate`: valid). `composer install` from the lock needs neither workaround |
| Deploy steps | `composer install --no-dev --optimize-autoloader`; `php artisan optimize:clear` (config, cache, compiled views, routes, events); `permission:cache-reset` not required (Permission unchanged) but harmless; model-cache keys unchanged (genealabs code identical), `cache:clear` still part of O6. **Do not run `route:cache` or `view:cache`** (fail today, §3.3). `config:cache` works on L11 (trial). After clearing compiled views, open a few pages once before traffic (cold-cache race, §3.13). No migrations, no DB change. Sessions and cookies: same config (`session.partitioned` false). **Run every `artisan` command (`optimize:clear`, `cache:clear`, `config:cache`, …) as the web server user, not as root** — or fix the ownership of `storage/` and `bootstrap/cache` afterwards (`chown -R <web user> storage bootstrap/cache`). Found in 3e: `optimize:clear` run as root in the dev container booted the app and left a root-owned `storage/framework/cache/data/0a` (mode 755); Apache (`www-data`) could not write the file cache and **264 of 429 golden-master URLs returned 500** until the ownership was fixed |
| Open items | O1 (MinIO `step-3` + AWS test bucket), O3 (PHP 8.4 / L11 on S3), O5 (PHP 8.4 server), O6, O7 (Mailgun via Symfony Mailer 7.4), O8 (manual checks; note the export known issue below) |

## 7. Risks and known issues

| # | Item | Mitigation |
|---|---|---|
| R-S3-1 | framework major (L11) + Symfony 7 | identical routes/middleware/config values (§3.3), suite, golden master, MinIO |
| R-S3-2 | plural form of `@choice` (L11 Translator) | Translator patch + `ChoiceTest` (§3.5) |
| R-S3-3 | `rehash_on_login` writes passwords | pin `false` (§3.4) |
| R-S3-4 | PHP 8.4 `round()` (no pre-rounding) | only in the unused `Files\ModulePresenter::filesize()`; if it is ever used, 14 of 6,453 sizes would round differently |
| R-S3-5 | PHP 8.4 deprecations (implicit nullable) and PHP 8.1 null-argument deprecations | logged to `null`, no behaviour change; fatal only in PHP 9 (D-S3-10) |
| R-S3-6 | `Content-Type` charset case | case-insensitive by the HTTP spec; D-S3-9 |
| R-S3-7 | Mailgun through Symfony Mailer 7.4 | O7 |
| R-S3-8 | S3 on PHP 8.4 / L11 | MinIO `step-3`; AWS test bucket (O1) |
| **Known issue (existing, not fixed)** | **The module Excel exports return 500 today** (L10, and identically on L11): `ItemList.vue` `exportUrl()` builds `fields` but never adds them to the query, so `Export::__construct()` calls `selectFields(null)` and the pinned core `Base::scopeSelectFields(…, string $fields)` throws a `TypeError`. Reproduced in a harness on both versions; not reachable by the suite or the golden master. Would show up in the O8 manual check. Fixing it needs a Vue or core change → Stage 2 / your decision, not part of the upgrade | — |
| Known issue (existing) | `route:cache` (duplicate route name `sr::`) and `view:cache` (missing `Modules/Files/resources/views`) fail on L10 and L11 | §6 |

## 8. Open questions / decisions for you

| ID | Question | Recommendation |
|---|---|---|
| **D-S3-1** | Docker base `php:8.4-apache-bookworm` (PHP 8.4.27, Debian 12, same as steps 1–2, O5 still open) | **yes** |
| **D-S3-2** | composer `php` constraint: `^8.4` or keep `^8.2` | **`^8.4`** (matches the tested runtime; Composer refuses an install on an older PHP) |
| **D-S3-3** | genealabs: 11.0.1 (same code as today) / 12.0.4 / 13.1.10 | **11.0.1**; choose 12/13 in step 4 with the same diff |
| **D-S3-4** | PHPUnit 11.5 + collision 8.9 now (required by collision 8; 2 technical test changes, §3.6) | **yes** |
| **D-S3-5** | Symfony: (a) all on 7.4 LTS (string / css-selector / event-dispatcher via temporary constraints) / (b) Composer's choice (those three on 8.1) / (c) those three on 6.4 (console and http-kernel stuck on 7.2.9, EOL) | **(a)** |
| **D-S3-6** | Carbon stays 2.73.0 in step 3; `formatLocalized` compat moves to step 4 | **yes** |
| **D-S3-7** | `hashing.rehash_on_login = false` | **yes** |
| **D-S3-8** | `@choice` plural form: (a) Translator patch in the pinned `typicms/translations` + `ChoiceTest` (output identical) / (b) accept L11 behaviour (search headings change for sr and sr-latn) | **(a)** |
| **D-S3-9** | `Content-Type` `charset=UTF-8` → `utf-8`: (a) accept and let `tools/golden-master/compare.php` compare the charset parameter case-insensitively (tooling only) / (b) pin `UTF-8` with a small global middleware in `app/` | **(a)** — not visible, charset names are case-insensitive; (b) adds application code for a header byte |
| **D-S3-10** | fix the 14 "implicitly nullable" deprecation sites (`?type`) | **no in step 3** (no behaviour effect, not required until PHP 9; keeps the step minimal; D6 still applies later: pinned copies yes, `Modules/` no) |
| **D-S3-11** | optional L11 skeleton modernisation (`bootstrap/app.php`, `bootstrap/providers.php`, `$middlewareAliases`, slim config) | **no** |
| **D-S3-12** | commit order §5 (3t, 3a, 3b green on L10; 3c–3d only green with 3e) | **yes** |
| D-S3-13 | Excel export known issue (§7): leave for Stage 2, or fix separately (needs a Vue or core change) | **leave for Stage 2**, documented; decide separately |

No item needs a change to the public site, module behaviour, Vue/frontend, `package.json`, the database or a test assertion.

Housekeeping: the trial container `s3trial` and image `akademija-step3-trial:php84` are still running/present (throwaway; trial-only edits: `no-api` on the Sidebar VCS repo, `logging.deprecations` channel, Apache on port 8080, the Translator patch); remove with `docker rm -f s3trial && docker rmi akademija-step3-trial:php84`. Trial captures `trial-step-3`, `trial-step-3a`, `trial-step-3b` and their compare folders are in the git-ignored `storage/golden-master/`.
