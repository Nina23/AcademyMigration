# STEP 5 PLAN — Laravel 12.70 → 13.x (final), PHP stays 8.4

**Analysis (2026-11-02); implemented 2026-11-02 / 03 (5a–5d), see §9 and UPGRADE_PLAN.md §18.** The analysis below is kept as written; corrections are in §9.
Branch `upgrade/step-5` created from `development` (`8aa13e6`, = `origin/development`, step 4 merged). Not pushed.

Checked against real code in the **throwaway trial** container `s3trial` (PHP 8.4.27 image, code = `git archive HEAD`, own vendor; the working tree, the `vendor` volume and the dev containers were not touched; artisan / PHPUnit there as `www-data`):
- a **trial resolution and install of Laravel 13.39.0** (explicit package list, no `-W`, Symfony not listed) and on it: the full suite, the **full golden master (429 URLs) served by the trial** vs `before-step-5`, the booted-app **dump-and-diff L12 vs L13** (config, all routes with middleware, HTTP middleware / groups / priority / aliases, events, providers), `route()` of every duplicated route name, `uri()` of **4,488 model × locale combinations**, the list of **Blade directives new in L13** vs all views, a **class load** of all 394 classes, a **method-collision check** (108 methods new in L13), the Excel export harness, the runtime deprecation log;
- source diffs of every framework class our compat code and patches depend on (R22, R29: RouteCollection, LengthAwarePaginator, AwsS3V3Adapter, FilesystemManager / Adapter, Translator, MessageSelector, FileLoader, TranslationServiceProvider, BladeCompiler, RouteUrlGenerator / UrlGenerator, SessionGuard, AuthManager, FormRequest, Handler, HandleExceptions) and of the packages that move (genealabs, pivot-events, ultrono, tinker).

## 1. Baseline (current state: Laravel 12.70.2, Carbon 3.15.1, PHP 8.4.27)

| Check | Result |
|---|---|
| Full suite (Docker, as `www-data`) | **83 tests, 622 assertions, OK** |
| Golden master `before-step-5` (429 URLs, 2026-11-02, after `view:clear` + warm-up) | 414 × 200, 14 × 404, 1 × 500 (`/api/news/1019/files`, the known 500) |
| `before-step-5` vs `after-step-4f` (same day) | **429 / 429 identical**. Reference for step 5 is `before-step-5` |

## 2. Summary

- **Laravel 13 does not change our output** once the step 4 compats are in place: on the trial (L13.39.0) the full suite is green and the golden master is **428 / 429 identical** to `before-step-5` — the one difference is the known capture artefact (§3.10). Unlike step 4, **no new behaviour compat is needed**: every step 4 compat (Translator, `Base::uri()`, `@context`, `RouteCollection`, paginator) is still needed on L13 and still works unchanged (R29 checked, §3.4).
- **Required:** composer (Laravel 13 + the packages that stop at L12: tinker 3, ultrono 10, eloquent-sortable 5, genealabs 13 (D-S5-1), dev: ignition 2.12, debugbar 4; transitive serializable-closure 2, brick/math 1.0, package-tools 1.93); **one pinned-copy patch**: `typicms/sitemap` composer.json `ultrono/laravel-sitemap ^9.5` → `^9.5|^10.0` (ultrono 10.0.0 is code-identical to 9.5.0); **one tooling change**: the golden master must normalise the new Debugbar 4 markup (D-S5-3).
- **Not required (verified):** Dockerfile / PHP (stays 8.4), Symfony (stays 7.4 LTS, D-S5-2), PHPUnit / collision, laravel/ui, Permission, Excel, Croppa, presenter, utilities, honeypot, typidesign, QB 6.4.4 / translatable / typicms copies (beyond the sitemap constraint), `config/*`, `app/` (all compat classes unchanged), tests, `phpunit.xml`.
- **CLAUDE.md check — modules, views, Vue, frontend, `package.json`, database, look and behaviour:** **nothing in `Modules/`, `resources/`, `public/`, `routes/`, `database/` or the JS/CSS build changes.** No behaviour change found that cannot be kept identical (no D-S4-8-like item in this step; §3.7 lists what was checked). No database write (genealabs 13 changes cache keys → `cache:clear`, a cache, not the DB).
- **Decisions for you** (§8).

## 3. Items

### 3.1 composer.json (L13)

Trial resolution, `composer update` with an explicit list, **no `-W`**, Symfony not listed:

| Package | Now (locked) | Target | Why / note |
|---|---|---|---|
| php | ^8.4 | = | L13 needs ^8.3; Docker stays `php:8.4-apache-bookworm` (8.4.27) |
| laravel/framework | ^12.0 (12.70.2) | **^13.0** → locks **v13.39.0** (2026-10-27, latest 13.x) | requires serializable-closure ^2.0.10, prompts ^0.3.25 (0.3.26 ok), brick/math ^0.14.2…\|^1.0, Carbon ^3.8.4, **Symfony ^7.4 \|\| ^8.0** |
| laravel/tinker | ^2.0 (2.11.1, ≤ L12) | **^3.0** (3.0.2) | console only (`php artisan tinker`); 1 file / 7 lines changed vs 2.11.1, config identical; no code uses it (UPGRADE_PLAN §1.1 planned ^3.0) |
| ultrono/laravel-sitemap | ^9.5 (9.5.0, ≤ L12) | **^10.0** (10.0.0) | **`src/`, views and config byte-identical to 9.5.0**; only constraints / docs changed → `/sitemap.xml` unchanged |
| typicms/sitemap (pinned copy) | requires `ultrono/laravel-sitemap ^9.5` | **`^9.5\|^10.0`** | the only pinned-copy patch of the step (composer.json only, `PATCHES.md`); without it Composer cannot install ultrono 10 |
| spatie/eloquent-sortable | ^4.5 (4.5.2, ≤ L12) | **^5.0** (5.0.1) | **not used by any code** (searched); bump only (UPGRADE_PLAN §1.1 planned ^5.0); D-S5-5 (bump or remove) |
| genealabs/laravel-model-caching | ^12.0.4 (≤ L12) | **^13.2** (13.2.1) | D-S5-1, §3.9; + `mikebronner/laravel-pivot-events` 12.0.0 → 13.1.0 |
| spatie/laravel-package-tools (transitive) | 1.92.7 (≤ L12) | 1.93.3 | used by the pinned QB copy and eloquent-sortable |
| laravel/serializable-closure (transitive) | 1.3.7 | **2.1.0** | required by L13; our code serializes no closures (no queued closures, no `Cache::flexible`) |
| brick/math (transitive) | 0.11.0 | **1.0.0** | required by L13; reached only through `ramsey/uuid` (unchanged 4.9.4) for `Str::uuid()` (new users' `api_token`, covered by the suite) |
| new (transitive) | | `symfony/polyfill-php86` | |
| spatie/laravel-ignition (dev) | 2.9.1 (≤ L12) | **^2.12** (2.12.1) | dev error page only; `config/ignition.php`, `config/flare.php` kept |
| barryvdh/laravel-debugbar (dev) | 3.16.0 (≤ L12) | **^4.4** (4.4.5) + `php-debugbar/php-debugbar` 3.8.1, new `php-debugbar/symfony-bridge` | dev only (not installed in production: `--no-dev`). Namespace is now `Fruitcake\LaravelDebugbar` (same package name); it no longer adds `InjectDebugbar` to the middleware stack; its injected HTML changed → D-S5-3. Published `config/debugbar.php` kept (defaults merged; `hide_empty_tabs` no longer read) |
| **Unchanged and verified for L13** | | Symfony **7.4.x** (D-S5-2), Carbon 3.15.1, laravel/prompts 0.3.26, laravel/ui 4.6.3, spatie/laravel-permission 6.25.0, maatwebsite/excel 3.1.70, bkwld/croppa 8.0.1, laracasts/presenter 0.2.9, laracasts/utilities 3.2.5, msurguy/honeypot 1.5.0, typidesign 1.0.4, phpunit 11.5.57, collision 8.9.5, league/flysystem-aws-s3-v3 3.32.0 + aws/aws-sdk-php **3.337.3 (pin kept, O1)**, Sidebar VCS fork `09d438e`, QB 6.4.4 / translatable 4.6.0 / nestablecollection / all other typicms copies | |

Trial lock result: **12 updates, 2 installs, 0 removals** (+ the `typicms/sitemap` metadata). Command (for 5c), after `composer require --no-update laravel/framework:^13.0 laravel/tinker:^3.0 ultrono/laravel-sitemap:^10.0 spatie/eloquent-sortable:^5.0 genealabs/laravel-model-caching:^13.2` and `--dev spatie/laravel-ignition:^2.12 barryvdh/laravel-debugbar:^4.4` (+ the temporary Sidebar `no-api`, removed before the commit; lock `content-hash` via Composer's `Locker::getContentHash()`, as in steps 3–4):
`composer update laravel/framework laravel/tinker ultrono/laravel-sitemap typicms/sitemap spatie/eloquent-sortable genealabs/laravel-model-caching mikebronner/laravel-pivot-events laravel/serializable-closure laravel/prompts spatie/laravel-ignition spatie/ignition spatie/flare-client-php spatie/error-solutions barryvdh/laravel-debugbar "php-debugbar/*" psy/psysh brick/math ramsey/uuid spatie/laravel-package-tools`

**Procedure note (found on the trial):** after `composer install --no-scripts`, the old `bootstrap/cache/packages.php` / `services.php` still name `Barryvdh\Debugbar\ServiceProvider`, so the app no longer boots and `artisan clear-compiled` cannot clear them. Delete the two files (as `www-data`) and run `package:discover`. A normal `composer install` with scripts (production) clears them without booting.

### 3.2 Symfony 7.4 or 8 — D-S5-2

L13 allows `^7.4 || ^8.0`. Not listing Symfony keeps every component on **7.4.x** (the trial resolves without touching them). Recommendation: **stay on 7.4 LTS** — it is the long-term-support line (bug fixes until Nov 2028, security until Nov 2029 under Symfony's LTS policy), it is what steps 3–4 verified, and 8.x is not an LTS line (its LTS will be a later 8.4). Moving to Symfony 8 would be a separate change with its own trial (Mailer / Mailgun transport, HttpFoundation, Console), not required by L13.

### 3.3 Blade directives (step 4 type R25)

New in L13: **`@fonts`** only. Searched every `.blade.php` in `resources/`, `Modules/`, `packages/` (case-insensitive, as Blade matches directives): **no `@fonts` text anywhere** → no collision. The step 4 `@context` directive is still needed (L13 still has `@context`) and still works (golden master).

### 3.4 R29 / R22: compat classes and patches against the L13 framework classes

| Our code | L13 framework class | Result |
|---|---|---|
| `App\Routing\RouteCollection` (`inNameLookup()` / `inActionLookup()` → `false`) | `Illuminate\Routing\RouteCollection`: domain routes now kept in separate lists (`domainRoutes`, `allDomainRoutes`); `addLookups()` / `refreshNameLookups()` / `refreshActionLookups()` **still call `inNameLookup()` / `inActionLookup()`** (first wins) | **still needed, still works**: `route()` for all 9 duplicated names identical to L11; no domain routes here |
| `App\Pagination\LengthAwarePaginator` (`linkCollection()` without `page`) | `linkCollection()` unchanged (**still adds `page`**); constructor guards `perPage` 0 | **still needed, still works**: 20 API lists identical |
| `App\Filesystem\AwsS3V3Adapter` (`url(null)`) (R22) | new `providesTemporaryUploadUrls()`; constructor and `url()` unchanged; `FilesystemManager` / `FilesystemAdapter` refactors (no effect on `createS3Driver()` with our config) | **no change**; `Api/FilesS3UrlTest` green; MinIO run at the end (§6) |
| `typicms/translations` Translator (`localeForChoice()`) (R23) | `localeForChoice($key, $locale)` unchanged (still falls back to `en`); new `string()` / `array()` helpers; `TranslationServiceProvider` byte-identical | **still needed, still works**: `ChoiceTest` green, search headings identical |
| `typicms/translations` MixedLoader | `FileLoader::loadJsonPaths()` now `array_replace` instead of `array_merge` | no effect: our JSON language files have **no numeric keys** (sr 527, sr-latn 518, en 506 keys, 0 numeric) |
| `Base::uri()` empty-slug patch (R26) | `RouteUrlGenerator` / `UrlGenerator` refactors; `route('en::news', '')` **still throws** | **still needed, still works**: `uri()` of 4,488 combinations identical to L11 |
| `Dates::formatLocalized()` | Carbon unchanged (3.15.1) | `LocalizedDatesTest` green |
| `tests/bootstrap.php` `flushState()` | `HandleExceptions::flushState()` still exists | 0 risky |
| `Handler::invalidJson()` override | parent `invalidJson()` identical | `ValidationMessageTest` green |
| QB 6.4.4 pinned copy (`clone()`), core / users / roles / translatable copies | L13 adds `BelongsToMany::clone()`, `Request::image()` — not classes our copies extend | no override (§3.6) |

### 3.5 Routes, URLs, API, auth, config (step 4 types)

- **Routes:** 277 vs 278 — only Debugbar's own `/_debugbar/*` routes changed (dev package, v3 → v4) and Laravel's `/.well-known/change-password` redirect also accepts the new `QUERY` method; all application routes identical with their middleware. **Duplicated route names:** identical to L11 (with `RouteCollection`).
- **HTTP middleware:** identical except Debugbar 3's `InjectDebugbar`, which Debugbar 4 no longer adds (dev only). Groups / priority / aliases identical.
- **`route()` / URL generation:** empty parameters still rejected (handled by `Base::uri()`); all `uri()` results identical.
- **API / paginator JSON:** all 26 `/api` URLs identical in the golden master (with the paginator compat).
- **Auth:** `SessionGuard` / `AuthManager` changes are `#[\SensitiveParameter]` attributes, null guards, enum names, custom-driver callback binding (we register no custom auth driver) → no behaviour change; remember-me as in step 4.
- **FormRequest:** new opt-in `failOnUnknownFields` (default `false`, only with an attribute none of our requests has) → no change.
- **Config:** **no existing value changed**; L13's config merging only adds keys (`images.default`, `broadcasting.connections.mercure`, `cache.stores.storage`, `logging.channels.monthly`, `queue.connections.background`, `laravel-model-caching.ttl` / `fallback-to-database`, Debugbar 4 keys).
- **Events / providers:** +2 framework closures (`Terminating`, `RequestHandled`), Debugbar 4's Octane listener and provider (dev).

### 3.6 Methods new in L13 vs our classes

108 method names are new on the framework base classes. Same-named methods in our code: `image()` (models / presenters; L13 adds `image()` only to `Request` / `FormRequest`, none of our FormRequests defines it) and `clone()` (pinned QB `QueryBuilder`, which implements `ArrayAccess` and does not extend `BelongsToMany`, where L13 added it) → **no override**.

### 3.7 Behaviour changes that cannot be kept identical

**None found.** Checked: auth / remember-me / sessions (§3.5), FormRequest, validation message (422), translations / plural forms, URL generation, route names, paginator, Blade, config merging, Carbon (unchanged), JSON language loading, genealabs cache (results, §3.9), the sitemap (ultrono code-identical), Excel (unchanged, still broken identically, O8). The only output-relevant change is Debugbar's dev-only HTML (D-S5-3), which does not exist in production.

### 3.8 Class load, deprecations, exports

- Class load (394 classes): 364 clean, 29 only the known PHP 8.4 implicit-nullable deprecations, 1 = the known `Setting` artefact — same as on L12.
- Runtime deprecations while serving 429 URLs: same types as in steps 3–4 (PHP 8.1 null-argument, implicit nullable); nothing new from L13.
- Excel exports: identical L12 / L13 (still the existing `TypeError`, O8, D-S3-13).

### 3.9 genealabs/laravel-model-caching on L13 — D-S5-1

| Option | Code vs installed 12.0.4 | Cache keys | Notes |
|---|---|---|---|
| **(a) 13.2.1** (recommended) | rewrite: 27 files / 3,090 changed lines (`CacheKey.php` alone 816), new classes (`CachedHasManyThrough`, `CachedMorphToMany`, `CachedQueryBuilder`, …), new config keys `ttl` (default `null`) and `fallback-to-database` (default `false`) | **all keys change** (now `builder:` + sha1 of the key) → **`cache:clear` mandatory on deploy** | the maintained L12–L13 line (also published as `mike-bronner/laravel-model-caching`); verified on the trial: suite green, golden master identical; + pivot-events 13.1.0 |
| (b) pinned copy of 12.0.4 (+ pivot-events 12.0.0), constraints widened to `^13` | identical to step 4 | unchanged | an unmaintained frozen copy of an abandoned package for the life of the site; **not verified on L13** (would need its own trial run) |

The cache layer is invalidated by model events; the suite runs with caching active (array store) and covers save / delete invalidation (`Admin/NewsAdminCrudTest`, `Models/*`, `Api/*`).

### 3.10 Golden master served by the L13 trial

Trial (L13.39.0, PHP 8.4.27, all step 4 compats, dev DB `akademija` read-only, captured inside the trial after a warm-up):

| State | Result vs `before-step-5` |
|---|---|
| L13, Debugbar 4 on, current `compare.php` | 27 same, **402 DIFF — all of them only Debugbar 4's new widget markup** before `</body>` (the current `compare.php` strips Debugbar 3 markup only) |
| L13, Debugbar off (trial `.env` only) | 27 same, 401 whitespace-only (each: one blank line fewer before `</body>`, where Debugbar 3 was stripped from the baseline), 1 DIFF = the capture artefact below |
| **L13, Debugbar 4 on, only-Debugbar filter** (D-S5-3 as accepted: removes only Debugbar's markup — the Debugbar 4 widget block, and Debugbar 3's script including the one `\n` Debugbar 3 adds after it; no general whitespace rule; tested in a scratchpad copy of `compare.php`) | **428 same, 1 DIFF**: `/likovne-umjetnosti/grafichki-dizaјn` `og:url` — the **capture artefact** known from steps 3–4 (Linux curl percent-encodes the Cyrillic `ј`, the Windows host does not); the real `after-step-5c` capture runs from the host. Control: `after-step-4f` vs `before-step-5` with the filter = 429 / 429; Debugbar 4 vs Debugbar off on the same trial = 429 / 429 (the filter removes exactly the Debugbar markup) |

Status codes identical (414 / 14 / 1); log: only the known `/api/news/1019/files` error.

## 4. What in UPGRADE_PLAN.md is wrong or outdated

1. **§1.1 L13 column:** laravel/framework ^13.0 → 13.39.0; genealabs **13.2.1** (D-S5-1); tinker ^3.0 (3.0.2); ultrono ^10.0 (code-identical to 9.5); eloquent-sortable ^5.0 (unused); symfony/mailgun-mailer + http-client stay **^7.4** (not `^7.4|^8.0`, D-S5-2); debugbar ^4.4 / ignition ^2.12 (dev); new transitive serializable-closure 2, brick/math 1.0.
2. **§3.4:** new pinned-copy patch `typicms/sitemap` composer.json (ultrono `^9.5|^10.0`).
3. **§8 step 5 row:** update per §5; "final composer.json cleanup (`php: ^8.4`)" is already done since step 3 (D-S5-5 for the rest).
4. **R29:** checked for L13 (§3.4) — all compat classes still needed and unchanged.
5. **O6:** `cache:clear` mandatory (genealabs 13 keys); `bootstrap/cache/packages.php` / `services.php` must be regenerated (normal `composer install` does it).

## 5. Proposed commit order (only the marked commits must be green; all prep commits are green on L12)

| # | Commit | Green? |
|---|---|---|
| **5a** | pinned `typicms/sitemap` composer.json: `ultrono/laravel-sitemap ^9.5\|^10.0` + `PATCHES.md`; lock metadata refreshed for `typicms/sitemap` only (still ultrono 9.5.0) | **yes** (L12): suite + golden master |
| **5b** | `tools/golden-master/compare.php`: remove only Debugbar's markup — the Debugbar 4 widget block, and Debugbar 3's body script with its own trailing `\n` (D-S5-3) + README | **yes**: tooling only; existing comparisons unchanged (e.g. `after-step-4f` vs `before-step-5` 429 / 429) |
| **5c** | composer.json + composer.lock (§3.1 list, Symfony 7.4, no `-W`); delete stale `bootstrap/cache` files + `package:discover` as `www-data` | **yes**: full suite, golden master `after-step-5c` vs a same-day baseline (warm-up after `view:clear`), routes / config dump, CORS, class load, R29 re-check on the installed classes |
| — | **final checks** (§6): MinIO run on L13 (announced: resets **only `akademija_s3test`**, never `akademija`), O9 logo check, §8 manual checks | — |
| 5d | documentation: UPGRADE_PLAN.md (§1, §3.4, §8, §9 R29, O1/O3/O6/O9, new §18 = step 5 record and final state), tests/README.md, `tools/s3-test/S3_TEST_REPORT.md` (final column), STEP5_PLAN.md | — |

There is no 5t: no L13 behaviour needed a new pin — every changed area is already pinned by the step 1–4 tests (`ChoiceTest`, `UriEmptySlugTest`, `RouteNameTest`, `PaginationLinksTest`, `JsonLdContextTest`, `LocalizedDatesTest`, `DateCastsTest`, `AppendsTest`, `FilesS3UrlTest`, …), and all of them are green on the L13 trial.

## 6. Checks at the end of the step (before 5d)

| Check | How | Rule |
|---|---|---|
| **MinIO run on L13** (O1; the final run, steps 4 and 5 together) | `tools/s3-test/setup.sh up` + `composer` (composer `--no-scripts` as root, artisan as `www-data`), `tools/s3-test/run.sh step-5`; compare with `step-3-*` | **announced before the run**; `setup.sh reset` drops and re-imports **only `akademija_s3test`**, never `akademija`. If it differs from step 3, repeat the run on `upgrade/step-4` to see which step it came from |
| **O9 logo check** | several rounds of `view:clear` + an immediate parallel capture (no warm-up), check `/sr/login` and all pages for truncated output; then look at `BladeCompiler::compile()` / `Filesystem::put()` for the cause | confirm or reject the cold compiled-view race; report before deploying (the deploy runs `view:clear`) |
| **§8 manual checks** (UPGRADE_PLAN §8, O8) | admin smoke per module (list, edit form; **saving only on the testing DB**, so in the browser by you on dev only after your approval), Excel export (known broken), `/sitemap.xml` (golden master), search, menus, blocks, history, settings, translations admin, users admin list, password reset / registration / e-mail verification pages, contact form on the `array`/`log` mailer, file-manager thumbnails | the read-only parts (rendering pages, comparing output) can be done by me; anything that saves or sends is yours to do or approve (CLAUDE.md database rule) |

## 7. Production (needed before deploying step 5 = the final version)

| Item | |
|---|---|
| PHP | **8.4** (unchanged), same extensions (STEP3_PLAN §6) |
| Composer | only `composer install` from the committed lock, Composer ≥ 2.7 |
| Deploy steps | as the web server user: `composer install --no-dev --optimize-autoloader` (its scripts regenerate `bootstrap/cache/packages.php` / `services.php`); `php artisan optimize:clear`; **`php artisan cache:clear` mandatory** (genealabs 13 cache keys); warm a few pages after `view:clear` (O9). Do not run `route:cache` / `view:cache`. No migrations, no DB change |
| Open items | O1 (final MinIO + AWS test bucket), O2, O3, O5, O6, O7 (Mailgun), O8 (manual checks, Excel known issue), O9 (logo) |

## 8. Open questions / decisions for you

| ID | Question | Recommendation |
|---|---|---|
| **D-S5-1** | genealabs: (a) 13.2.1 (maintained L12–L13 line, rewrite, new cache keys, verified on the trial) / (b) a pinned copy of 12.0.4 with widened constraints (frozen, abandoned, not yet verified on L13) | **(a)** |
| **D-S5-2** | Symfony: stay on 7.4 LTS / move to 8.x | **7.4 LTS** (L13 allows it; LTS until Nov 2028 / 2029; 8.x not LTS) |
| **D-S5-3** | golden master vs Debugbar 4: (a) `compare.php` removes Debugbar's markup / (b) capture with `DEBUGBAR_ENABLED=false` (needs new baselines) | **(a)** — **accepted with the owner's condition**: the filter removes **only** Debugbar's markup (no general whitespace rule), and after 5c one **independent comparison with no filter** is run: a before and an after capture on the same day, both with Debugbar disabled (`DEBUGBAR_ENABLED=false` set as a container environment variable through a compose override outside the repo — no repo file changed); both results are shown side by side and any other difference is explained individually |
| **D-S5-4** | laravel/framework: lock 13.39.0 (2026-10-27, latest) | **yes** |
| **D-S5-5** | final composer.json cleanup: (a) nothing beyond the bumps (`php ^8.4` already) / (b) also remove the unused `spatie/eloquent-sortable` and `msurguy/honeypot` | **(a)**; removing unused packages is a Stage-2 cleanup, not needed for the upgrade |
| **D-S5-6** | tinker 3, ultrono 10, ignition 2.12, debugbar 4 (all required by L13, no runtime / output change) | **yes** |
| **D-S5-7** | commit order §5 (no 5t; 5a / 5b green on L12; 5c composer; final checks; 5d docs) | **yes** |
| **D-S5-8** | §8 manual checks: I do the read-only parts; the saving / sending parts are done by you (or explicitly approved one by one) | **yes** |

No item needs a change to `Modules/`, the views, Vue / frontend, `package.json`, a test, or the database.

Housekeeping: the trial container `s3trial` (L13.39.0 with the trial-only `no-api`, deprecation log channel, `DEBUGBAR_ENABLED=false`, Apache on 8080) is kept for the implementation; remove with `docker rm -f s3trial && docker rmi akademija-step3-trial:php84`. Trial captures `trial-step-5`, `trial-step-5b` and their compare folders are in the git-ignored `storage/golden-master/`; the prototype `compare.php` is only in the scratchpad.

## 9. Implementation notes (2026-11-02 / 03)

Commits as in §5: `b044a03` 5a, `bf1ffab` 5b, `900e918` 5c, then 5d (docs). Full record: UPGRADE_PLAN.md §18.

- **5a / 5b / 5c as planned.** 5c lock = the trial lock (164 packages). Suite 83 / 622 OK after each commit; golden master 429 / 429 after 5a, 5b and 5c (same-day baselines, warm-up first). The stale discovery cache (§3.1 procedure note) happened in 5c too: deleted, then `package:discover` as `www-data`.
- **D-S5-3 condition fulfilled:** the unfiltered comparison with Debugbar off on both sides is 429 / 429 with `compare.php`; raw bytes: 156 identical, 272 only CSRF token values, 1 only the debug stack trace of the known `/api/news/1019/files` 500 (UPGRADE_PLAN §18.1).
- **§6 final checks:**
  - **MinIO:** identical to step 3, trace identical. The 12,110-byte larger 404 pages are explained by the S3 test env's `array` cache with genealabs 11 (incomplete menus), not by the upgrade; production uses `file` → no change (O1, S3 report difference 7). No repeat on `upgrade/step-4`.
  - **O9:** **stays open.** On dev the cold rounds reproduced a *different* symptom from the step 4b logo incident: 500 `File does not exist` on 3 other pages at parallelism 12, caused by the Windows bind mount (confirmed with a control on the container filesystem). The logo incident (200 without the logo) was not reproduced and stays unexplained. Both are to be checked on the Linux test server (UPGRADE_PLAN O9).
  - **§8 read-only checks:** admin pages L11 vs L13 identical (apart from the render setup), password-reset forms identical, Excel exports unchanged. Found **`/admin/sections` 500 on both versions** → UPGRADE_PLAN O10, to be fixed in this phase after 5d, in its own commit, after the owner approves the cause and the fix. The saving / sending checks are the owner's, on the test server (UPGRADE_PLAN §18.4).
- **Corrections to this plan:**
  - §6 described the O9 cause as `BladeCompiler::compile()` writing non-atomically. That was read from the host's stale `vendor/` (Laravel 8.30). Laravel 13 writes compiled views atomically (`Filesystem::replace()`). Framework code must be read in the container, which has its own `vendor` volume.
  - §8 D-S5-8: the admin render opened `/admin/cache/clear` (a GET route that flushes the cache) and `/admin/dashboard` (outbound GET, log line). The dev DB was checked read-only and is unchanged. Both routes and `admin/_locale/{locale}` are now in the render's skip list (UPGRADE_PLAN §18.3).
- **Housekeeping:** `s3trial` was removed on 2026-11-03. The image `akademija-step3-trial:php84` is kept until the owner decides. The S3 containers are stopped.
