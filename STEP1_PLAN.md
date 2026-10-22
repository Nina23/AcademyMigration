# STEP 1 PLAN — Laravel 8.83 → 9.52, PHP 7.4 → 8.1

**Analysis only (2026-10-20), decisions recorded the same day (§6).** No code, config, composer or test file was changed yet; implementation waits for approval of this version. Branch `upgrade/step-1` was created from `development` (19d4a56 = `origin/development`); nothing committed or pushed. Source of truth stays `UPGRADE_PLAN.md`; this file details Step 1 against the real code and lists what in the plan turned out wrong.

How it was checked: real files in the repo; Laravel 9.52.16 / 10.48 / 13.0 sources, Croppa 8.0.1, Spatie Permission 6.25.0, Query Builder 5.7.0, ultrono/laravel-sitemap 9.5.0, genealabs 0.12.5 sources (downloaded and diffed); Packagist metadata; `composer why-not php 8.1`; a **Composer dry-run of the complete Step 1 dependency set** (temporary composer file outside the project, PHP 8.1 platform; project `composer.json`/`composer.lock` untouched); `php -l` of all project PHP files on PHP 8.1 (`php:8.1-cli`); a slug comparison on 9,793 real strings from the dev DB (read-only).

---

## 1. Starting point

### 1.1 What Step 0 delivered (verified on `development`)

| Item | Plan | Actual |
|---|---|---|
| Pinned copies in `packages/` | all `typicms/*` + `spatie/laravel-translatable` | **yes**: 17 `typicms/*` (incl. `events`, `objects`, `bootforms`, `form`, `laravel-translatable-bootforms`, `nestablecollection`) + `spatie/laravel-translatable` 4.6.0, path repository, symlinked into `vendor/`, each with `PATCHES.md` |
| Laravel 8.83 | 8.30 → 8.83 | **yes**: `laravel/framework` v8.83.29, PHP 7.4.33 |
| D1 feed removal | remove route, action, `laravelium/feed`, tests | **yes** |
| D2 `typicms/events`, `typicms/objects`, `eluceo/ical` | the original plan said "remove" | **not removed in step 0** (D2 then said keep all typicms packages); still in `composer.json` and `packages/`. They do not block PHP 8.1, but are unused → **now decided: remove them in a separate commit before 1b** (D-S1-2, §3.12) |
| D7 `temporaryUrl()` fallback for local disks | applied | **yes** (`AppServiceProvider::boot()`, `Storage::extend('local')`) |
| Hotfix `files-s3-exists` | merge into upgrade branch | **yes**, merged into `development` (PR #3) |

### 1.2 Baseline test result (Docker, `upgrade/step-1` = `development`)

```
PHPUnit 9.6.38 — Laravel 8.83.29, PHP 7.4.33
OK (64 tests, 484 assertions)   failures 0, errors 0, skipped 0, incomplete 0   (1:41 min)
```

Golden master: last capture `after-d7` (2026-10-06). **A fresh baseline capture should be taken on the day Step 1 starts** (content changes over time, see `tools/golden-master/README.md`).

---

## 2. Summary

- **Step 1 is feasible without touching `Modules/` or any public view/layout/CSS/JS/route.** All changes are in `docker/`, `composer.*`, `app/` (3 files), `config/` (4 files), two pinned package copies (`typicms/roles`, `typicms/sitemap`), and one technical test line (§11 of the plan).
- **The complete dependency set resolves** (dry-run with the decisions applied: events/objects/eluceo removed, `aws/aws-sdk-php` pinned to 3.337.3): Laravel 9.52.22, Croppa 8.0.1 + Intervention Image 3.11, Permission 6.25, Query Builder 5.7, Flysystem 3.36 + **`flysystem-aws-s3-v3` 3.32.0** (newest 3.x that accepts aws-sdk 3.337; 3.34+ need ^3.371.5), ultrono sitemap 9.5, genealabs 0.12.5, `symfony/mailgun-mailer` + `http-client` 6.4. 19 packages are removed (fideloper, fruitcake, facade/ignition, swiftmailer, laravelium/sitemap, phpthumb, typicms/events, typicms/objects, eluceo/ical, …).
- **D2:** `eluceo/ical` 0.16.1 allows PHP ≥ 7.1 and does **not** block PHP 8.1; `typicms/events`/`objects` are unused. **Decided: remove all three** (unused), separate commit before 1b (§3.12).
- **Croppa crops disk (decided, option c):** crops disk chosen by `FILESYSTEM_DRIVER`; on S3 a `croppa_crops` disk (s3 config + `url = APP_URL`) keeps the production thumbnail redirect on the app host as today; locally the default disk, so dev and `FileUploadTest` stay as they are (§3.6).
- **Only known public-site difference:** newly generated Croppa thumbnails are encoded by Intervention 3 instead of PHPThumb (D4, accepted). URLs, tokens and existing crops are unchanged.
- **Decisions:** all recorded in §6; nothing left open for starting implementation except your approval of this version.

---

## 3. Items

Legend for "impact": **Public** = public website (views, layouts, CSS/JS, routes, URLs, rendered output); **Modules** = behaviour of anything in `Modules/` (routes, URLs, slugs, validation, admin forms, API responses, exports, observers, presenters).

### 3.1 Dockerfile → PHP 8.1

| | |
|---|---|
| Current | `FROM php:7.4-apache` + 4 `sed`/`echo` lines that switch bullseye to `archive.debian.org` |
| Change | **`FROM php:8.1-apache-bookworm`** (pinned Debian release, D-S1-4; switch to the production OS once you confirm it); **remove the 4 archive workaround lines** (bullseye-specific; the floating `php:8.1-*` tags are now Debian 13 *trixie* without `/etc/apt/sources.list`, verified). Packages (`libjpeg62-turbo-dev`, `libonig-dev`, …), extensions and `composer:2.2` stay (2.2 LTS supports PHP 8.1) |
| Files | `docker/php/Dockerfile` |
| Public / Modules | no / no (development container only) |
| Risk | low. Image rebuild + `composer install` into the `vendor` volume |
| Confirmed by | container builds; `php -v` 8.1; full suite |

### 3.2 composer.json (each package)

| Package | Now | Step 1 | Required? | Note |
|---|---|---|---|---|
| php | ^7.3 | **^8.1** | yes | L9 needs ≥ 8.0.2; 8.1 needed by Croppa 8, Intervention 3, Symfony mailer 6.4 |
| laravel/framework | ^8.0 (8.83.29) | **^9.52** (resolves 9.52.22) | yes | |
| fideloper/proxy | ^4.2 | **remove** | yes | built into L9 (§3.3) |
| fruitcake/laravel-cors | ^2.0 | **remove** | yes | built into L9 (§3.4) |
| bkwld/croppa | ^4.10 (4.11.1) | **^8.0.1** | yes | 4.x max L8 (§3.6) |
| genealabs/laravel-model-caching | ^0.11.0 (0.11.7) | **^0.12.5** | yes | 0.11 = illuminate ^8; 0.12.5 = illuminate ^9, same `Cachable`/`flushCache` API, identical config keys (diffed). Pulls `laravel-pivot-events` 9.0.4 |
| laravelium/sitemap | ^8.0 | **→ ultrono/laravel-sitemap ^9.5** | yes | §3.8 |
| league/flysystem-aws-s3-v3 | ~1.0 | **^3.0** (resolves **3.32.0** because of the aws-sdk pin) | yes | §3.7, **production S3** |
| **new** aws/aws-sdk-php (direct pin) | transitive 3.337.3 | **`3.337.3`** | decided (D-S1-5) | keeps the S3 client exactly as installed by step 0b until the MinIO tests (O1); without the pin Step 1 would pull 3.401.3 |
| spatie/eloquent-sortable | ^3.9 | **^4.5** (4.5.2) | yes | 3.x = illuminate ^8; not used by any code |
| spatie/laravel-permission | ^3.17 | **^6.25** | yes | §3.10 |
| spatie/laravel-query-builder | ^3.2.3 | **^5.7** | yes | §3.11 |
| **new** symfony/mailgun-mailer, symfony/http-client | — | **^6.4** | yes | §3.9 |
| guzzlehttp/guzzle | ^7.0.1 | `^7.2` (optional) | no | 7.15 installed already fits L9 |
| eluceo/ical | ^0.16.0 | **remove** (commit 1a2, before 1b) | decided (D-S1-2) | allows PHP ≥ 7.1, so it does not block PHP 8.1; removed because it is unused (only required by `typicms/events`) |
| typicms/events, typicms/objects | 9.0.4, 8.0.4 (pinned copies) | **remove** + delete `packages/typicms/events`, `packages/typicms/objects` (commit 1a2) | decided (D-S1-2) | unused (§3.12) |
| laracasts/presenter, laracasts/utilities | ^0.2.4, ^3.2 | unchanged | no | 0.2.9 / 3.2.5 (from step 0b) already allow L9 |
| laravel/ui | ^3.0 (3.4.6) | unchanged | no | 3.4.6 allows `illuminate ^8.42\|^9.0` |
| laravel/tinker | ^2.0 (2.11.1) | unchanged | no | allows L6–L12 |
| maatwebsite/excel | ^3.1 (3.1.70) | unchanged | no | allows L5.8–L13. Note: L9 pulls `maennchen/zipstream-php` 3.x (xlsx writer) → R13 manual export check |
| maatwebsite/laravel-sidebar | 1.1.6 (VCS) | unchanged | no | `illuminate >=5.0` |
| msurguy/honeypot | ~1.1.5 (1.1.7) | unchanged | no | 1.1.7 allows `9.*` (plan said ^1.5.0: not needed) |
| typidesign/laravel-artisan-translations | ~1.0.3 (1.0.4) | unchanged | no | 1.0.4 (step 0b) requires `php ^7.0\|^8.0` (plan said ^2.0: not needed) |
| typicms/*, spatie/laravel-translatable (pinned copies) | exact | unchanged | — | constraints already widened in step 0a; sitemap copy patched (§3.8) |
| **require-dev** facade/ignition | ^2.3.6 | **→ spatie/laravel-ignition ^1.7** | yes | facade/ignition is L7/L8 only (dev error page) |
| fzaninotto/faker | ^1.9.1 | **→ fakerphp/faker ^1.24** | yes | fzaninotto requires `php ^7.0` → blocks PHP 8.1 (only package besides root that does, `composer why-not php 8.1`) |
| nunomaduro/collision | ^5.0 | **^6.4** | yes | v6 for L9 |
| phpunit/phpunit | ^9.3 (9.6.38) | `^9.6` (optional) | no | L9 needs ≥ 9.5.10, installed 9.6.38 |
| mockery/mockery | ^1.3.1 (1.6.15) | `^1.6` (optional) | no | |
| barryvdh/laravel-debugbar | ^3.5 (3.7.0) | unchanged constraint | no | 3.7.0 allows L9; a full `composer update` moves it to 3.16 (dev only) |

Transitive changes worth noting (from the dry-run): `aws/aws-sdk-php` stays 3.337.3 (pinned; unpinned it would go to 3.401.3), `psr/http-message` 2.0, `nette/utils` 4, `doctrine/instantiator` 2, `league/flysystem` 3.35 + `league/flysystem-local`, `intervention/image` 3.11 (+ `intervention/gif`), `spatie/ignition` 1.14, Symfony 5.4 → **6.4**.

Public / Modules: no / no by themselves; behaviour of each package change is covered in the items below.

### 3.3 TrustProxies + config/trustedproxy.php

| | |
|---|---|
| Current | `app/Http/Middleware/TrustProxies` extends `Fideloper\Proxy\TrustProxies`, `$proxies` unset, `$headers = Request::HEADER_X_FORWARDED_ALL`. `config/trustedproxy.php`: `'proxies' => null` (hard-coded, no env), `'headers' => Request::HEADER_X_FORWARDED_ALL` |
| Change | base class → `Illuminate\Http\Middleware\TrustProxies`; `$headers = Request::HEADER_X_FORWARDED_FOR \| HEADER_X_FORWARDED_HOST \| HEADER_X_FORWARDED_PORT \| HEADER_X_FORWARDED_PROTO` (= what Symfony 5.4 maps `HEADER_X_FORWARDED_ALL` to, verified in its `Request::setTrustedProxies`); same constant expression in `config/trustedproxy.php` (otherwise the config file fatals: the constant does not exist in Symfony 6) |
| Files | `app/Http/Middleware/TrustProxies.php`, `config/trustedproxy.php` |
| Public / Modules | **no / no**. No proxy is trusted today (`proxies` null in class and config), and L9's middleware still falls back to `config('trustedproxy.proxies')` (verified), so no `X-Forwarded-*` header is honoured before or after |
| Correction to the plan | L9.52's `TrustProxies::getTrustedHeaderNames()` is a `match` over single constants; a combined bitmask falls into the `default` arm (FOR\|HOST\|PORT\|PROTO\|**PREFIX**\|AWS_ELB). L10+ returns an int bitmask as-is. Irrelevant here because no proxy is trusted; it would only matter if `trustedproxy.proxies` is ever set during the L9 step |
| Risk | very low |
| Confirmed by | `Boot/*`, full suite (requests are not proxied) |

### 3.4 Kernel CORS middleware

| | |
|---|---|
| Current | `app/Http/Kernel.php` `$middleware`: `\Fruitcake\Cors\HandleCors::class` (after TrustProxies). `config/cors.php`: `paths ['api/*']`, all methods/origins/headers, `max_age 0`, no credentials |
| Change | `\Illuminate\Http\Middleware\HandleCors::class` at the same position. `config/cors.php` unchanged (same keys, read by L9's middleware) |
| Files | `app/Http/Kernel.php` |
| Public / Modules | **no / no**: only `api/*` responses carry CORS headers; the admin Vue app calls `/api` same-origin |
| Differences found (diffed) | fruitcake 2.x additionally added headers through a `RequestHandled` listener and skipped responses that already had `Access-Control-Allow-Origin`; L9 adds them only in the middleware. Underlying service changes from `asm89/stack-cors` to `fruitcake/php-cors` |
| Risk | low; only response headers of `/api/*` |
| Confirmed by | full suite (no header assertions exist); proposed manual check: `curl -i` a few `/api/*` requests (with and without `Origin`, plus an `OPTIONS` preflight) before and after, compare headers |

### 3.5 Handler::invalidJson()

| | |
|---|---|
| Current | `app/Exceptions/Handler.php` has no `invalidJson()`. L8 `ValidationException` message = literal `"The given data was invalid."` |
| Change | add `protected function invalidJson($request, ValidationException $exception)` returning `['message' => 'The given data was invalid.', 'errors' => $exception->errors()]` with `$exception->status` |
| Why | L9 `ValidationException::summarize()` uses the first error text ("…(and N more errors)") as message. The admin shows `error.response.data.message` (`FileManager.vue` 451/576/767, `ItemList.vue` 347/439/505/546, `History.vue`), so without the override the visible admin texts would change |
| Files | `app/Exceptions/Handler.php` |
| Public / Modules | no / **no with the override** (API 422 JSON identical) |
| Risk | low |
| Confirmed by | `Images/FileUploadTest::…disallowed_types_are_rejected` (422 + errors). no test pins the message today → **new test in commit 1t (§3.14)** |

### 3.6 Croppa 8 + config/croppa.php (+ crops disk / tmp_disk)

| | |
|---|---|
| Current | Croppa 4.11.1 (PHPThumb). `config/croppa.php`: `src_dir`/`crops_dir` = `'filesystem.default.driver'`, `path 'storage/(.*)$'`, `ignore '\.(gif\|GIF)$'`, `signing_key 'app.key'`, `memory_limit 128M`, `jpeg_quality 75`, `interlace true`, `upscale true`, 6 filters |
| Change (decided D-S1-1 = option c) | Croppa 8 keys: `src_disk` = the default disk; **`crops_disk` = `'croppa_crops'` when the default disk is `s3`, otherwise the default disk**; **`tmp_disk` = `'local'`**; `quality` 75 (Croppa 8 default is 95), `upsize` true (default false), `interlace` true; `path`, `ignore`, `signing_key`, `max_crops`, `memory_limit`, `filters` unchanged. A comment in `config/croppa.php` explains why (below) |
| How the disk is chosen | in `config/croppa.php` read **`env('FILESYSTEM_DRIVER', 'local')`** — the same source as `filesystems.default`. Not `config('filesystems.default')`: Laravel loads config files in name order (`LoadConfiguration`, `ksort`), so `croppa.php` is evaluated before `filesystems.php` and `config('filesystems.default')` would still be `null` there. Works the same with `config:cache` |
| `croppa_crops` disk | defined in `config/filesystems.php` as **the `s3` disk configuration (same bucket, credentials, region, endpoint, visibility, `throw`) with `'url' => env('APP_URL')`** (the `s3` array is built once and reused, so the two cannot drift apart). Only resolved when the default disk is `s3`; in dev/tests it is never instantiated |
| Effect on S3 (production) | Croppa 8 writes/finds crops at the same keys in the same bucket and answers the rendered crop with `301 Location: Storage::disk('croppa_crops')->url($cropPath)` = `APP_URL/files/<crop>` — the same target as today's Croppa 4 redirect `/files/<crop>` on the app host (absolute instead of relative URL; same page in the browser, **provided `APP_URL` in production is the public site URL** → OPEN). Deleting a file removes source and crops in the same bucket as now |
| Effect locally (dev, tests) | `crops_disk` = `public` exactly as now: crops written to the (faked) `public` disk and served directly, no redirect → `FileUploadTest` unchanged |
| Verified identical | URL generation (`URL::generate`), signing token (`md5(app.key + basename)`, `app.key` resolved the same way), route `{path}` registered in the provider's `boot()` with the same pattern, `Helpers::url/delete`, class names `Bkwld\Croppa\{URL,Handler,Helpers,Storage}` (used by tests), alias `Croppa` (now `Bkwld\Croppa\Facades\Croppa` via package discovery, so `use Croppa;` keeps working) |
| Differences | (1) image engine PHPThumb → Intervention 3 (GD): **new** crops are re-encoded, may differ slightly in sharpness/compression (D4, accepted); (2) route pattern also accepts `.webp` (a request for a non-existing `storage/…-WxH.webp` now goes to Croppa instead of the Pages catch-all; edge case); (3) `url()` is now typed `(string, ?int, ?int, ?array): string` — all call sites pass ints/null (core `Presenter::image()`); (4) on S3 the 301 for a rendered crop points to `Storage::disk(crops_disk)->url()`; with the `croppa_crops` disk that is `APP_URL/<cropPath>`, i.e. the same target as today — must still be verified on MinIO / a test bucket (O1) and against production's current behaviour (O2(c)) before any deploy |
| Options used | only `resize` (via the Files presenter, which no longer calls Croppa) and plain width/height; Croppa 8 parses `-resize` from the URL as before |
| Files | `config/croppa.php`, `config/filesystems.php` (`croppa_crops` disk) |
| Public / Modules | **Public: yes, minimal** — core-presenter thumbnails on public pages (Announcements, Classschedules, Departments, …) are re-encoded when newly generated (D4). Same URL, same size/crop. Existing crops are served unchanged. In production these models currently show `img-not-found.png` anyway (Stage-2 issue). Modules: no |
| Risk | medium (image library swap, S3 redirect) |
| Confirmed by | `Images/FileUploadTest` (7 tests: URL pattern, token, render on request 240×180 with `resize`, `img-not-found`, delete source + crops, tampered token) |

### 3.7 Flysystem 3 + config/filesystems.php

| | |
|---|---|
| Current | Flysystem 1, `s3` disk keys `key, secret, region, bucket, url, endpoint, visibility public`; `public`/`local` local disks |
| Change | `s3` disk: add `'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)` and **`'throw' => true`** (decided D9 / D-S1-6: only on `s3`, not on `local`/`public`); new disk **`croppa_crops`** = the same `s3` array with `'url' => env('APP_URL')` (§3.6), so it inherits `throw => true`. Croppa 8 catches `FilesystemException` in `writeCrop()` as Croppa 4 did, so a failed crop write behaves as today. Nothing else |
| Verified (Laravel 9.52 / 13.0, flysystem-aws-s3-v3 3.x) | `url()` same logic; presigned `temporaryUrl()` same; `public-read` ACL from `visibility`; `has()` = file or directory as before; with `throw` false writes/deletes would return `false` → `throw => true` keeps the L8 behaviour; `exists()` now throws on S3 errors; `getAdapter()->getPathPrefix()` removed (affects only Croppa 4 and the test line, §3.13); D7 callback never applies to S3 |
| Files | `config/filesystems.php` |
| Public / Modules | no / no (same URLs; same upload/delete behaviour with `throw => true`) |
| Risk | **high for production S3, not verifiable locally** → OPEN O1/O3 |
| Confirmed by | `Images/FileUploadTest` (local disk only). S3: MinIO later (D8 postponed) |
| Decided | `throw` only on `s3` (D-S1-6). Dev note: on the local disks a failed write returns `false` in L9 instead of an exception (dev only) |

### 3.8 ultrono/laravel-sitemap

| | |
|---|---|
| Current | `laravelium/sitemap` 8.0.1; `typicms/sitemap` requires `laravelium/sitemap ~8.0` and only uses `app('sitemap')`, `setCache`, `isCached`, `add`, `render('xml')`; published views `resources/views/vendor/sitemap/*`, `config/sitemap.php` |
| Change | root: remove `laravelium/sitemap`, add `ultrono/laravel-sitemap ^9.5`; pinned copy `packages/typicms/sitemap/composer.json`: `laravelium/sitemap ~8.0` → `ultrono/laravel-sitemap ^9.5` (+ `PATCHES.md`) |
| Verified | ultrono 9.5.0 `SitemapServiceProvider`, `Sitemap`, `Model` are **identical** to laravelium 8.0.1 apart from the namespace (`Ultrono\Sitemap`) and doc comments (normalized diff). Same `sitemap` binding, view namespace and config keys |
| Files | `composer.json`, `packages/typicms/sitemap/composer.json`, `packages/typicms/sitemap/PATCHES.md` |
| Public / Modules | no / no — `/sitemap.xml` output identical (golden master URL #4) |
| Risk | low. Note: ultrono 9.5 allows up to L12; L13 needs 10.x (plan step 3) |
| Confirmed by | golden master `/sitemap.xml` |

### 3.9 symfony/mailgun-mailer + symfony/http-client

| | |
|---|---|
| Current | `MAIL_MAILER=mailgun` (dev and production), `config/mail.php` `mailgun` mailer, `services.mailgun` `domain/secret/endpoint`; Swiftmailer. Mail code: `App\Mail\ContactMail` (public contact form, `Pages PublicController::contactAdmin`, `from()` = visitor's email), `typicms/users` reset-password / verify-email notifications. No Swiftmailer-specific code (`withSwiftMessage`, `getSwiftMessage`) anywhere |
| Change | add `symfony/mailgun-mailer ^6.4`, `symfony/http-client ^6.4`. Config unchanged |
| Public / Modules | no / no (same page response after sending; same views) |
| Risk | medium: real delivery through Mailgun cannot be tested locally (no credentials, must not use production ones). Symfony Mailer validates addresses strictly (as Swiftmailer did); the contact form validates `email` |
| Confirmed by | full suite (`array` mailer); manual on `log` mailer; real Mailgun → OPEN |

### 3.10 spatie/laravel-permission 6 + Role patch (packages/typicms/roles)

| | |
|---|---|
| Current | Permission 3.18. `TypiCMS\Modules\Roles\Models\Role implements Spatie\Permission\Contracts\Role` |
| Required patch (fatal otherwise) | v6 contract: `findByName(string, ?string): self`, `findById(int\|string, ?string): self`, `findOrCreate(string, ?string): self`, `hasPermissionTo($permission, ?string $guardName): bool`. TypiCMS Role has `findById(int $id, …)` (narrower → fatal) and `hasPermissionTo($permission)` (missing parameter → fatal). Patch: `findByName(string $name, ?string $guardName = null): self`, `findById(int\|string $id, ?string $guardName = null): self`, `findOrCreate(string $name, ?string $guardName = null): self`, `hasPermissionTo($permission, ?string $guardName = null): bool`; inside `hasPermissionTo`, `getPermissionClass()` now returns a **class-string** → call `$permissionClass::findByName(...)`/`::findById(...)` statically. Queries and logic unchanged |
| Verified unchanged | tables via our `table_names` (`role_user`, `permission_user`, `permission_role`), `model_morph_key = model_id`; `role_pivot_key`/`permission_pivot_key` missing in our config → v6 falls back to `role_id`/`permission_id`; `teams` missing → false; `register_permission_check_method` (Gate) comes from the merged package config (true). `typicms/users` `User` (uses `HasRoles`) has no conflicting overrides (`getAllPermissionsAttribute` is an accessor, different name). `Roles AdminController` `firstOrCreate` is on the Permission model, fine |
| **New finding** | the permission cache key stays `spatie.permission.cache`, but v3 stored a Collection and v6 reads `$cached['alias']` → **a stale v3 cache entry breaks permission checks** until the cache is cleared. Tests use the array cache (not affected). **Dev: `php artisan cache:clear` after the switch. Production: `php artisan permission:cache-reset` (or `cache:clear`) in the deployment** → OPEN list. The plan (R10 "rebuilt automatically") was wrong |
| Existing quirks (unchanged) | `Role::users()` declares return type `MorphToMany` without import, `Role::create()` throws `RoleAlreadyExists` without import — both already broken paths today, not touched |
| Files | `packages/typicms/roles/src/Models/Role.php`, `packages/typicms/roles/PATCHES.md`, `composer.json` |
| Public / Modules | no / no (admin authorization; same tables, same results) |
| Risk | medium |
| Confirmed by | `Admin/AdminAccessTest::test_permissions_are_enforced_per_action`, `Api/NewsApiTest::test_api_enforces_permissions`, `Auth/*` |

### 3.11 spatie/laravel-query-builder 5.7

| | |
|---|---|
| Current | QB 3.6.3. Usage: `allowedSorts` ×33, `allowedFilters` ×33 (all `AllowedFilter::custom(..., new FilterOr())`), `allowedIncludes` ×15, `allowedFields` ×2 (`typicms/history`, `typicms/users` incl. `roles.name`); no `allowedAppends` anywhere |
| Change | composer only. **No code change** |
| Verified | **CORRECTION (found in 1c, see §7.2): the statement that appends are not used was wrong.** QB 3.6.3 applies every `append=` value of the request to the results even without `allowedAppends()` (`QueryBuilder::__call` → `addAppendsToResults`); v5 removed appends, so `thumb`, `department`, `formatted_year` disappear from the admin lists. Rest of the original check: v4 stopped camel/snake-converting names — our fields/tables are already snake_case and the related-fields key for `roles` resolves to `roles` in both (v5 `convert_relation_names_to_snake_case_plural` default true). `allowedFields()` is called before `allowedIncludes()` (required since v2). `Filter` interface `__invoke(Builder $query, $value, string $property)` without return type → core `FilterOr` (`: Builder`) stays valid |
| Files | `composer.json` |
| Public / Modules | no / no (admin API JSON identical) |
| Risk | low |
| Confirmed by | `Api/*` (8 tests), golden master (26 API URLs with the Vue admin's query strings) |

### 3.12 typicms/events, typicms/objects, eluceo/ical (D2)

| | |
|---|---|
| Current | kept and pinned (your D2 decision) |
| Finding | `composer why-not php 8.1`: only the root `php ^7.3` and `fzaninotto/faker` block 8.1. `eluceo/ical` 0.16.1 requires `php >=7.1 \|\| ~8.0.0` (accepts 8.1). PHP 8.1 lint: `typicms/events` 26 files OK, `eluceo/ical` 23 files OK; `typicms/objects` 6 files fail (`class Object` reserved) — **identically on PHP 7.4 today**; `objects` has no autoload section, no provider, no reference → never loaded |
| Usage (verified by you and here) | not registered in `config/app.php`; no reference in `app/`, `config/`, `routes/`, `Modules/` or published views (`resources/views/vendor/` has no `events`/`objects`); no other package requires them; no DB tables. The only mention is inside a Blade comment `{{-- --}}` in `resources/views/vendor/pages/public/home.blade.php` (`Events::upcoming()`), which Blade does not compile — **that file is not touched** |
| Change (decided D-S1-2) | **commit 1a2, on Laravel 8.83 / PHP 7.4, before 1b:** remove `typicms/events`, `typicms/objects`, `eluceo/ical` from `composer.json`; delete `packages/typicms/events` and `packages/typicms/objects`; `composer update typicms/events typicms/objects eluceo/ical` (lock: only these three removed, everything else identical — checked like D1). No other file changes |
| Public / Modules | no / no |
| Risk | very low |
| Confirmed by | full suite **identical** to the baseline (64 tests, 484 assertions — the count must not change); `route:list` unchanged |

### 3.13 Technical test change (UPGRADE_PLAN §11)

| | |
|---|---|
| Current | `tests/Feature/Images/FileUploadTest.php:43`: `$this->app->make('Bkwld\Croppa\Storage')->getSrcDisk()->getAdapter()->getPathPrefix()` |
| Change | `->getSrcDisk()->path('')` (Croppa 8 returns a Laravel `FilesystemAdapter`; Flysystem 3 has no `getPathPrefix()`). Same assertion (root contains `storage/framework/testing/disks/public`) |
| Line 37 | the list of reset singletons stays as is (all names still exist: `filesystem.disk`, `filesystem.default.driver`, `Bkwld\Croppa\Storage/Handler/Helpers`) |
| Public / Modules | no / no |

### 3.14 New test pinning the 422 validation message (D-S1-3)

| | |
|---|---|
| Change | **commit 1t, first, on Laravel 8.83 / PHP 7.4:** one new test file (proposed `tests/Feature/Api/ValidationMessageTest.php`), existing tests untouched. Scenario as in the admin file manager: authenticated superuser uploads a disallowed type (`script.php`) to `POST /api/files` → assert `422`, `message === 'The given data was invalid.'`, and the `errors.name` key (same setup as `FileUploadTest`: `Storage::fake('public')`, `apiHeaders()`) |
| Why | pins the API contract the admin Vue shows (`error.response.data.message`); on L9 it only stays green with `Handler::invalidJson()` (§3.5) |
| Public / Modules | no / no |
| Confirmed by | green on L8 (baseline becomes **65 tests**); must stay green after 1c |

### 3.15 Other Laravel 9 / PHP 8.1 checks (no change needed)

| Check | Result |
|---|---|
| PHP 8.1 lint (`app`, `Modules`, `config`, `routes`, `database`, `tests`, `packages`) | 0 errors except the never-loaded `typicms/objects` (same on 7.4) |
| Classes in `app/Http/Kernel.php` and `config/app.php` | all exist in L9.52 except `Fruitcake\Cors\HandleCors` (replaced, §3.4) |
| Overrides of framework classes in pinned copies | `MixedLoader::load(): array` vs untyped parent (allowed); `TranslationServiceProvider::registerLoader()` same; `NestableCollection` only overrides `__construct($items = [])` (same); `AbstractFormRequest` `messages()/attributes()` same |
| `MixedLoader` `$this->path`/`loadPath()` | still exist in L9 → its patch stays in Step 2 |
| `Str::slug()` (`voku/portable-ascii` 1.6 → 2.x): new tag slugs (`Tags\HasTags`), uploaded file names (`FileUploader`), `SlugObserver` | **0 differences on 9,793 real strings** (news/announcement/page titles sr, sr-latn, en; tags; file names; biography names; extra Cyrillic/Latin/diacritics samples) |
| Symfony 6 `InputBag::get()` with arrays | no `$request->get()` / `->query->get()` in `app`, `Modules`, `packages` |
| Removed `password` rule, `dispatchNow`, `BelongsToMany::firstOrCreate` change | not used |
| Pagination | `Paginator::useBootstrap()` → `pagination::bootstrap-4` in L8 and L9; project publishes `resources/views/vendor/pagination` |
| Deprecations on PHP 8.1 (e.g. `strftime` via Carbon `formatLocalized`, implicit nullable) | Laravel logs them to the `deprecations` channel (`null`); PHPUnit 9.6 does not convert them; no behaviour change. `formatLocalized` replacement stays in step 3 |
| `$dates` in 5 module models | still supported in L9 → stays in Step 2 (§4 of the plan) |

---

## 4. What in UPGRADE_PLAN.md is wrong or outdated

| # | Plan says | Reality | Proposed fix in the plan |
|---|---|---|---|
| 1 | §0 row 8, §1.1, §1.2, §8 step 0 row, §12 D2: `typicms/events`, `typicms/objects`, `eluceo/ical` block PHP 8.1 and are removed in step 0 | They were kept in step 0. They do not block PHP 8.1; they are **removed in Step 1 (commit 1a2) because they are unused** | reword D2 and the rows accordingly; step-0 row: "kept, removed in step 1" |
| 2 | §1.1 / §1.2 eluceo "0.16 refuses PHP ≥ 8.1" | `eluceo/ical` 0.16.1 allows PHP ≥ 7.1 (`>=7.1 \|\| ~8.0.0`), so it does not block PHP 8.1; it is removed because it is unused | correct the wording exactly so |
| 3 | §2.2 TrustProxies bitmask "exactly the old Symfony 5 value" | true for the constant, but L9.52's middleware ignores combined bitmasks (default arm adds PREFIX); L10+ honours them. No effect here (no trusted proxies) | add the note |
| 4 | §2.2 / §2.3 `croppa_crops` disk with `url = APP_URL` for every environment, and `src_disk`/`crops_disk` = `config('filesystems.default')` in `config/croppa.php` | a `croppa_crops` disk in every environment conflicts with `FileUploadTest` (fakes only `public`, asserts crops on `public`); `config('filesystems.default')` is still `null` while `croppa.php` loads (config files load in name order) | decided D-S1-1 (c): `croppa_crops` only when `FILESYSTEM_DRIVER` is `s3`, read via `env()` (§3.6) |
| 13 | §2.3 Croppa 8: no `url_prefix`, so the S3 redirect "changes to the S3 URL" | kept identical by the `croppa_crops` disk (`url = APP_URL`), configuration only | update §2.3 "Croppa redirect" |
| 14 | §1.1 flysystem-aws-s3-v3 "^3.25" final | with `aws-sdk-php` pinned to 3.337.3 the adapter resolves to 3.32.0 (3.34+ need aws-sdk ^3.371.5) | note the pin and lift it after O1 |
| 5 | R10 "cache rebuilt automatically" | stale v3 permission cache breaks v6 | deployment step `permission:cache-reset`; OPEN list |
| 6 | §1.1 L9 column: msurguy/honeypot ^1.5.0, typidesign ^2.0, debugbar ^3.16 | not required: 1.1.7 / 1.0.4 / 3.7.0 already accept L9 / PHP 8.1 | mark optional |
| 7 | §1.1 laravel/framework ^9.52 → 9.52.16 | latest 9.x is 9.52.22 | none (constraint unchanged) |
| 8 | D10: branch `upgrade/step-1` from `upgrade/step-0` | step 0 is merged; branched from `development` | update D10 |
| 9 | §8 Branch line "`upgrade/laravel-13` created from `setup/tests`" | per-step branches from `development` | update |
| 10 | AWS SDK only noted for step 0b | unpinned, Step 1 would pull `aws/aws-sdk-php` 3.401.3 | decided D-S1-5: pin 3.337.3 until O1 |
| 11 | Dockerfile "drop the archived-Debian workaround" | required, not optional: `php:8.1-*` is Debian trixie without `/etc/apt/sources.list` | note |
| 12 | R13 Excel 3.1.70 | also `maennchen/zipstream-php` 2 → 3 under it (xlsx writer) | add to R13 manual export check |

---

## 5. Order of commits (Step 1) — approved

Branch `upgrade/step-1`. **Full suite in Docker after every block**; golden-master compare after 1b and 1c. Within 1c only the last commit must be green (D-S1-7).

0. **Before starting (no commit):** fresh golden-master capture `before-step-1` (read-only); record `curl -i` headers of a few `/api/*` requests incl. an `OPTIONS` preflight (CORS reference).
1. **1t — new test pinning the 422 message** (D-S1-3, §3.14), Laravel 8.83 / PHP 7.4. Suite: 65 tests, all green.
2. **1a — `fzaninotto/faker` → `fakerphp/faker`** (dev dependency; the only non-root PHP 8.1 blocker). Suite identical.
3. **1a2 — remove `typicms/events`, `typicms/objects`, `eluceo/ical`** (D-S1-2, §3.12): composer.json, delete the two pinned copies, lock shows only these three removed. Laravel 8.83 / PHP 7.4. **Suite identical** (same counts). `home.blade.php` and all public views untouched.
4. **1b — PHP 8.1 on Laravel 8.83:** Dockerfile `php:8.1-apache-bookworm` without the archive workaround (§3.1), root `"php": "^7.3|^8.1"`, rebuild image, `composer install`. Suite + **golden master** (expected: identical).
5. **1c — Laravel 9.52 and everything that must change with it** (review commits, last one green):
   - 1c-1 composer: §3.2 (framework ^9.52, croppa ^8.0.1, flysystem-aws-s3-v3 ^3.0, **aws/aws-sdk-php 3.337.3**, genealabs ^0.12.5, eloquent-sortable ^4.5, permission ^6.25, query-builder ^5.7, ultrono ^9.5, mailgun-mailer + http-client ^6.4, laravel-ignition ^1.7, collision ^6.4; remove fideloper, fruitcake, laravelium/sitemap, facade/ignition); root `"php": "^8.1"`
   - 1c-2 `app/`: `TrustProxies`, `Http/Kernel` (CORS), `Exceptions/Handler::invalidJson()`
   - 1c-3 `config/`: `trustedproxy.php`, `filesystems.php` (`use_path_style_endpoint`, `'throw' => true` on `s3`, `croppa_crops`), `croppa.php` (Croppa 8 keys, crops disk by `FILESYSTEM_DRIVER` via `env()`, `tmp_disk` `local`, commented)
   - 1c-4 pinned copies: `typicms/roles` `Role` (Permission 6 signatures, static permission lookups), `typicms/sitemap` composer (`ultrono/laravel-sitemap`), each with `PATCHES.md`
   - 1c-5 technical test change §3.13 (`FileUploadTest.php:43`)
   - then `cache:clear` in dev; suite (65 tests green) + **golden master** + CORS header compare
6. **1d — documentation:** `UPGRADE_PLAN.md` corrections (§4 of this file, incl. the D2/eluceo wording), additions to "OPEN BEFORE DEPLOYING TO PRODUCTION" (below), `tests/README.md` (65 tests, new test listed).
7. Manual checks (plan §8, after step 1): admin smoke per module, Excel exports (zipstream 3), file manager upload/delete + thumbnails, sitemap, search, menus, blocks, history, settings, translations, users list, password reset / verify pages, contact form on the `log` mailer.

### Additions to "OPEN BEFORE DEPLOYING TO PRODUCTION" (written in 1d)

- **O1 (unchanged, extended):** MinIO / test-bucket checks now also cover Step 1: Flysystem 3 + `flysystem-aws-s3-v3` 3.32, `'throw' => true`, the **`croppa_crops` s3 branch** (crop rendered, stored in the bucket at the same key, 301 to `APP_URL/<cropPath>`, reused on the next request, deleted with the file), `tmp_disk` copy of an S3 source, `temporaryUrl()`, upload/delete. Lift the `aws/aws-sdk-php` 3.337.3 pin only after these pass.
- **O2(c) (unchanged):** where today's production thumbnail redirect points, compared with the `croppa_crops` result. Plus: **`APP_URL` in production must be the public site URL** (the crop redirect is built from it).
- **O5 Production server (new, D-S1-9):** PHP **8.1** with `gd`, `intl`, `zip`, `pdo_mysql`, `bcmath`, `mbstring`, `curl`, `xml` (same set as the Dockerfile); OS/Debian release confirmed and the Dockerfile tag aligned to it.
- **O6 Deployment steps (new, D-S1-9):** after deploying Step 1: `php artisan permission:cache-reset`, `php artisan cache:clear`, `php artisan view:clear`, `php artisan config:clear` (or `config:cache`), `php artisan route:clear`. The permission cache reset is mandatory (stale Permission v3 cache breaks v6, §3.10); the model cache (genealabs 0.11 → 0.12) is cleared by `cache:clear`.
- **O7 Mailgun (new, D-S1-9):** send the contact form and a password-reset mail through Mailgun with a **non-production** Mailgun domain/key (sandbox), never the production credentials; verify delivery and the `From`/`Reply` headers.

---

## 6. Decisions (recorded 2026-10-20)

| ID | Decision |
|---|---|
| Commit order | **approved:** 1a (faker) → 1b (PHP 8.1 on L8.83) → 1c (L9) → 1d (docs), full suite after each, golden master after 1b and 1c; plus 1t (422 test) first and 1a2 (D2 removal) before 1b (§5) |
| **D-S1-1** Croppa crops disk | **option (c):** in `config/croppa.php`, by `FILESYSTEM_DRIVER`: `s3` → `crops_disk = 'croppa_crops'` (s3 config + `'url' => env('APP_URL')`), production redirect stays `/<cropPath>` on the app host; otherwise → the default disk (dev and `FileUploadTest` unchanged). `src_disk` = default disk, `tmp_disk` = `'local'`. Clear comment in the file. No test, module or public view changes. O1 and O2(c) stay OPEN; the s3 branch must be verified on MinIO / a test bucket before any deploy. Implementation note: read `env('FILESYSTEM_DRIVER', 'local')` (config load order, §3.6) |
| **D-S1-2** events / objects / eluceo | **remove** all three (unused), delete `packages/typicms/events` and `packages/typicms/objects`; separate commit 1a2 before 1b on L8.83 / PHP 7.4; suite must stay identical; `home.blade.php` and other public views untouched; fix UPGRADE_PLAN wording (eluceo 0.16.1 allows PHP ≥ 7.1, removed because unused) |
| **D-S1-3** 422 message | **add one new test** on Laravel 8 (commit 1t); existing tests untouched |
| **D-S1-4** Docker base image | **`php:8.1-apache-bookworm`** until you confirm the production OS; then align |
| **D-S1-5** AWS SDK | **pin `aws/aws-sdk-php` 3.337.3** until the S3 tests (→ `flysystem-aws-s3-v3` 3.32.0) |
| **D-S1-6** `throw` | **`'throw' => true` only on `s3`** (inherited by `croppa_crops`) |
| **D-S1-7** 1c granularity | several review commits allowed; only the last one green |
| **D-S1-8** optional bumps | **none** (honeypot, typidesign, debugbar, phpunit/mockery constraints stay) |
| **D-S1-9** production prerequisites | added to OPEN as O5 (server PHP 8.1 + extensions), O6 (deploy cache resets incl. `permission:cache-reset`), O7 (Mailgun test without production credentials) |

Nothing is implemented until you approve this version of the plan.

---

## 7. Findings during implementation (1c, 2026-10-21)

### 7.1 Permission 6 default guard → `typicms/users` patch (fixed, commit `dc8d83c`)

- **Finding:** spatie/laravel-permission 6 changed `Guard::getDefaultName()`: it prefers the guard of the current request when the model allows it. The TypiCMS `User` allows `web` and `api` (both guards use the `users` provider), so on the admin API (`auth:api`) permission checks looked for the permission under guard `api`; all 70 permissions and 3 roles use guard `web` → every non-superuser got 403 (`NewsApiTest::test_api_enforces_permissions` failed). Permission 3 always used the model's first guard.
- **Fix (approved):** pinned copy `packages/typicms/users/src/Models/User.php` overrides `getDefaultGuardName()` with the Permission 3 logic (first guard of the model). `getGuardNames()` unchanged. Recorded in `packages/typicms/users/PATCHES.md`.
- **For 1d (UPGRADE_PLAN.md):** add the patch to §3.4 (required patches) and the finding to R10 (Permission 6); add `packages/typicms/users` to the Step 1 file list.

### 7.2 Query Builder 5 removed `append=` → admin lists lose appended columns (OPEN, decision needed)

- **Golden master `before-step-1` vs `after-step-1c`:** all public pages, sitemap, search and auth pages identical (409 of 429). 19 paginated admin API lists differ (+ `/api/history`, two dev-DB rows written in between).
- **Cause 1 (visible in the admin):** QB 3.6.3 applies every `append=` value of the request (`append=thumb`, `append=department,formatted_year`) to all result models, without `allowedAppends()`; QB 5 removed appends. Missing now: `thumb` in the lists of Announcements, Banners, Biographies, Categories, Classschedules, Departments, Menus, News, Newsletters, Page sections (518 rows; the admin thumbnail column), `department` + `formatted_year` (+ the `announcement_department` relation) in Classschedules. **This is a module behaviour change and must not ship.**
- **Cause 2 (JSON type only):** Laravel 9 `LengthAwarePaginator` casts `per_page` to int (`(int) $perPage`); Laravel 8 kept the request string (`"100"` → `100`). The admin Vue uses `parseInt(data.per_page)` and string concatenation, so nothing visible changes.
- **Decision 1 (appends) = option A, implemented:** pinned copy `packages/spatie/laravel-query-builder` 5.7.0 with the Query Builder 3.6.3 append behaviour restored (`AppendsAttributesToResults` verbatim, hooks for Model / Collection / paginators in `QueryBuilder::__call()`), documented in its `PATCHES.md`. New test `tests/Feature/Api/AppendsTest.php` (commit `8b1c6a9`, 1c-7a): passes on Laravel 8.83 / QB 3.6.3 (throwaway install of commit 0e3c2f4), fails at 1c-6, passes with the fix (commit `96072ac`, 1c-7b). Suite 67 tests, 497 assertions.
- **Decision 2 (per_page) = option i:** accept the integer (`"100"` → `100`); the only consumer is the admin Vue (`parseInt(data.per_page)`, string concatenation).
- **Result:** golden master `before-step-1` vs final `after-step-1c`: 409 identical; the 20 other URLs differ only by the `per_page` type, and `/api/history` additionally by the two dev-DB rows written in between. CORS headers identical.
- **For 1d (UPGRADE_PLAN.md):** (1) §1.1/§3.4: Query Builder is now a pinned copy (5.7.0 + append patch); **the append patch must be carried into the Query Builder 6.x copy in step 2**; (2) §7 API contract / risk register: `per_page` is an integer from Laravel 9 on (accepted); (3) correct §1.1/§3.4 "QB 5/6: no module change" — true only with the append patch.

### 7.3 Manual check for R16 (egulias/email-validator 4.0.4)

- Laravel 9 pulled `egulias/email-validator` 2.x → 4.0.4 (used by `email` / `email:rfc,dns` rules in Users admin and registration). **Manual check after Step 1:** in the admin, save a user with a normal e-mail address (e.g. `ime.prezime@au.unibl.org`) → saved without validation error; and an obviously invalid one → rejected as before. For 1d: add to UPGRADE_PLAN §8 manual list and R16.
