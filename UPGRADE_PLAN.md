# UPGRADE PLAN — PHP 7.4 → 8.4, Laravel 8 → 13

**Phase 1: analysis only. No code, config, test or database file was changed while this was written.**
Everything below is a proposal for approval.

- Current state: PHP 7.4.13, `laravel/framework` v8.30.1, TypiCMS 8.x packages (`typicms/core` 8.0.11), 13 local modules in `Modules/`, Vue admin via `/api`, MariaDB 10.11.19.
- Target: PHP 8.4, Laravel 13 (verified against `laravel/framework` v13.34.0 source).
- Reference (`typicms-main`) was used **only** for package versions and structure. It has no `vendor/` and an empty `Modules/`: in TypiCMS 17 every module (Pages, Menus, Files, Users…) lives inside one `typicms/core` package. More on that in §3.

How this was verified (not guessed):
- Every PHP file in `app/`, `Modules/`, `config/`, `routes/`, `database/`, `tests/` and the relevant `vendor/` packages was linted with **PHP 8.4** (Docker `php:8.4-cli`).
- **Rector 2 dry-run** (PHP 8.4 compatibility rules) was run over `app/`, `Modules/` and the TypiCMS packages. Nothing was written.
- The source of the target versions was downloaded and diffed against what is installed: Laravel 13, spatie/laravel-translatable 6, spatie/laravel-permission 6/8, spatie/laravel-query-builder 5/6/7, bkwld/croppa 8/9, laravel/ui 4, typicms/sidebar 2, typicms/nestablecollection 6, genealabs 13, ultrono/laravel-sitemap 10, maatwebsite/excel 3.1/4.
- Package compatibility per Laravel major was read from Packagist metadata.

---

> **Status (2026-10-29):** step 0 and step 1 (Laravel 9.52 / PHP 8.1, §14) merged into `development`. **Step 2 (Laravel 10.50.3 / PHP 8.2) done on branch `upgrade/step-2`** (not pushed, not merged), see §15. **Local S3 test on MinIO done** for the pre-upgrade code, step 1 and step 2 (O1, `tools/s3-test/S3_TEST_REPORT.md`); it found one S3-only regression in step 1, fixed (1e); step 2 is identical to step 1. Nothing is deployed: see "OPEN BEFORE DEPLOYING TO PRODUCTION".

## 0. Executive summary

| # | Finding | Consequence |
|---|---|---|
| 1 | **Every TypiCMS upgrade path changes behavior.** Each core major (10 → 11 → 12 → 16 → 17) rewrote the modules, merged them into `typicms/core`, and changed the DB schema, Vue admin, API and routing. | Upgrading `typicms/*` is not an option. **Keep the exact installed 8.x code as pinned in-repo copies (path repositories)** and apply only small compatibility patches. The patches are listed in §3. |
| 2 | **Laravel 13 still supports the Laravel 8 application skeleton**: HTTP/Console Kernel, `config/app.php` provider list, `RouteServiceProvider::map()`, `$routeMiddleware`, old-style `bootstrap/app.php`, and `public/index.php`. All checked in v13.34.0 source. | **No migration to the slim Laravel 11 structure.** Provider and middleware order stay identical (the Pages catch-all route must stay last). About 5 small edits in `app/` are needed (§2). |
| 3 | `spatie/laravel-translatable` 6 (the only line that supports Laravel 11+) changes the stored JSON format (unescaped unicode), the fallback logic, and the null/`''` return values. Several of these are pinned by `TranslatableStorageTest`. | Keep **v4.6.0 as a pinned in-repo copy.** It runs on Laravel 13 unchanged; only its composer constraint must be widened. |
| 4 | `spatie/laravel-permission` **6.25 supports Laravel 8.12 → 13** and `spatie/laravel-query-builder` **6.4 supports Laravel 10 → 13 and still accepts arrays.** | **No module call site has to change.** Query Builder 7 (variadic arguments) and Permission 8 are not needed. |
| 5 | Only **5 model files in 4 modules** need a required change: `protected $dates` was removed in Laravel 10. Affected: News, Advertismentboards, Announcements, Classschedules (2 models). | Equivalent `'datetime'` casts (§4). |
| 6 | PHP 8.4: **no fatal incompatibility** in `app/` or `Modules/`. Only deprecations (implicit nullable params, null to internal functions), which Laravel routes to the `deprecations` log channel (`null`). | No PHP-syntax change is *required* in modules. The optional Rector fixes are listed in §6. |
| 7 | **Database: no change required.** No schema change, no new migration, no data change (§5). | One config pin prevents Laravel 11+ from rewriting password hashes at login (`hashing.rehash_on_login=false`). |
| 8 | **Decisions** (§12): News RSS feed (`laravelium/feed`, no Laravel 9+ version, removed in D1), the unused packages `typicms/events`, `typicms/objects`, `eluceo/ical` (removed in step 1; they did not block PHP 8.1), and the Croppa image engine (thumbnails generated after the upgrade are encoded by a different library). | See decisions D1–D10 and D-S1-1…9. |
| 9 | **Production stores all files on Amazon S3**; dev and tests use the local `public` disk, so S3 is not covered by tests or the golden master. Flysystem 1 → 3 (L9) changes S3 error handling (writes return `false` instead of throwing) and Croppa 8 changes the thumbnail redirect target and needs a local `tmp_disk` | §2.3. S3 checks against MinIO / a separate test bucket after steps 1 and 5 (§8, D7, D8). The production bucket is never used for tests |

Recommended order: **5 framework steps, one major at a time (8 → 9 → 10 → 11 → 12 → 13)**, plus a preparatory step 0. Each step ends with the full test suite green (§8).

---

## ⚠ OPEN BEFORE DEPLOYING TO PRODUCTION

**Nothing from this upgrade (including step 0 and the D1/D7 commits) may be deployed to production until every item below is closed.** Production stores all files on Amazon S3; dev and the test suite use the local disk, so the S3 path is not verified locally yet (D8 postponed on 2026-10-06). The production bucket and production credentials are never used for testing and nothing is written to them.

| # | Item | Applies to | Status |
|---|---|---|---|
| O1 | **Local S3 test (MinIO, D8)** with its own bucket and credentials and a separate DB copy (not `akademija`): upload (image + PDF; object under `files/`, ACL `public-read`, `Content-Type`, `_1` suffix on a duplicate name), `Storage::url()` links (admin file form, public `_documents` link), `Storage::temporaryUrl()` (presigned, expires after 5 min), an image through Croppa (first request renders, stores the crop, 301; note the target; second request reuses the crop), admin file-manager thumbnails (`thumb_sm`), deleting a file (source and all crops removed), an S3 failure during upload. Run it on the **pre-upgrade code first** to record the reference, then on every step. Step 0 is included: step 0b already updated `aws/aws-sdk-php` 3.173 → 3.337.3 (R19). **Step 1 adds:** Flysystem 3 + `league/flysystem-aws-s3-v3` 3.32, `'throw' => true` on `s3` (a failed upload must raise an error and save nothing), the `croppa_crops` branch (crop rendered from the S3 source via the local `tmp_disk`, stored in the bucket at the same key, `301` to `APP_URL/<cropPath>`, reused on the next request, deleted with the file). Lift the `aws/aws-sdk-php` 3.337.3 pin only after these pass. **Result 2026-10-22 (MinIO, `tools/s3-test/`, report `tools/s3-test/S3_TEST_REPORT.md`):** pre-upgrade code (`development`, L8.83 / PHP 7.4) and step 1 (L9.52 / PHP 8.1) give the same result for every check: same objects, `Content-Type` and `x-amz-acl: public-read` on every write, `_1` suffix, links, temporary URL (`X-Amz-Expires=300`, refused after expiry), Croppa crop rendered/stored/reused/deleted, 500 and nothing saved on an S3 failure. Expected differences only: the Croppa redirect is absolute (`APP_URL/<cropPath>`, same target, D-S1-1(c)), and Laravel 9 fails earlier on an S3 error (`UnableToCheckFileExistence` in the name check instead of the AWS error), same outcome. **One step 1 regression found and fixed:** on S3 `url(null)` threw a `TypeError` → `/api/files` 500 for every folder with sub-folders (§14, 1e). **Step 2 (2026-10-29, L10.50.3 / PHP 8.2):** identical to step 1 in every check, S3 request trace identical line by line; the only difference is a 19-byte larger 404 page (Laravel 10 `csrf_field()` adds `autocomplete="off"` in the logged-in navbar, not S3-related, report difference 5). **Still open:** the MinIO run on every later step (steps 3–5); a separate non-production **AWS test bucket** with the production bucket settings for what MinIO cannot reproduce (per-object ACLs, Object Ownership, Block Public Access, see the report); the `aws/aws-sdk-php` pin (keep it until the AWS test bucket run) | step 0, steps 1–5, D7 | **MinIO done for step 0 code, step 1 and step 2**; open for steps 3–5 and the AWS test bucket |
| O2 | **Production checks (read-only, done by the owner):** (a) is `AWS_URL` set in the production `.env`; (b) the bucket's Object Ownership / ACL setting (ACLs enabled? uploads send `public-read`) and Block Public Access; (c) where a Croppa thumbnail redirects to today: open a core-presenter thumbnail (e.g. an announcement image in the admin list, URL `/storage/…-x54.jpg?token=…`) in a browser and note the status and the `Location` of the 301, and whether the image finally loads — compare with the step 1 `croppa_crops` result (`APP_URL/<cropPath>`). On MinIO (2026-10-22) both branches answer `301` to the same target (L8 `/files/x-240x180.jpg`, step 1 `http://localhost:8081/files/x-240x180.jpg`), and **that target returns 404 on the app on both branches** (nothing serves `/<cropPath>` locally); whether production serves it is exactly this check; (d) **`APP_URL` in production is the public site URL** (the crop redirect is built from it) | Croppa decision (§2.3, D-S1-1), R18, R20 | open |
| O3 | **Every change in steps 1–5 whose S3 behaviour could not be verified locally**, listed here as each step is done. **Step 1 (done, not verified on S3):** Flysystem 3 + `league/flysystem-aws-s3-v3` 3.32.0 (§2.3), `config/filesystems.php` (`$s3` once, `use_path_style_endpoint` default false, `'throw' => true`, new `croppa_crops` disk), Croppa 8 (`config/croppa.php`: crops disk by `FILESYSTEM_DRIVER`, `tmp_disk` `local`, quality 75, upsize), `aws/aws-sdk-php` pinned 3.337.3; since 1e the `s3` driver is registered in `AppServiceProvider` (`App\Filesystem\AwsS3V3Adapter`, `url(null)`). **All of these verified on MinIO 2026-10-22 (O1)**; still unverified on AWS itself (per-object ACL, Object Ownership, Block Public Access, AWS DNS/regions) → AWS test bucket. **Step 2:** no S3-related package or config changed (Flysystem adapter 3.32.0, AWS SDK 3.337.3, Croppa 8.0.1 unchanged; `Illuminate\Filesystem\AwsS3V3Adapter` byte-identical, R22); framework 10.50.3 + PHP 8.2 verified on MinIO 2026-10-29 (identical to step 1). Later: step 3+ PHP 8.4 / newer AWS SDK; any later `aws/aws-sdk-php` update | steps 1–5 | open |
| O4 | D7 (`temporaryUrl()` fallback for local disks, commit `e4497d6`): verified with a dummy S3 disk config (no network) that an `s3` disk gets no callback and still returns a presigned URL and the same `url()`. Confirm once more on MinIO (O1). **Confirmed on MinIO 2026-10-22** (pre-upgrade code and step 1): the `s3` disk gets no callback and returns a real presigned `GetObject` URL (`X-Amz-Algorithm=AWS4-HMAC-SHA256`, `X-Amz-Expires=300`); it opens, and after 305 s MinIO refuses it (`AccessDenied: Request has expired`); `url()` unchanged. Re-checked with O1 in every later step | D7 | done on MinIO (re-check with O1 per step) |
| O5 | **Production server:** PHP **8.1** (step 1) / **8.2** (step 2) with `gd`, `intl`, `zip`, `pdo_mysql`, `bcmath`, `mbstring`, `curl`, `xml` (the Dockerfile set); confirm the production OS/Debian release and align the Dockerfile tag (now `php:8.2-apache-bookworm`, D-S1-4 / D-S2-7) | steps 1–2 | open |
| O6 | **Deployment steps** after deploying **each** step: `php artisan permission:cache-reset`, `php artisan cache:clear`, `php artisan view:clear`, `php artisan config:clear` (or `config:cache`), `php artisan route:clear`. Step 1: the permission cache reset is mandatory: a cached Permission 3 entry breaks Permission 6 (§14, R10); `cache:clear` also drops the genealabs 0.11 model cache. Step 2: `cache:clear` is mandatory too, the genealabs 0.13.9 cache key format differs from 0.12.5 (R11); the Permission cache format is unchanged (6.25) | steps 1–5 | open |
| O7 | **Mailgun:** send the contact form and a password-reset mail through Mailgun (Symfony Mailer since step 1) with a **non-production** Mailgun domain/key (sandbox), never the production credentials; check delivery and the `From` header | step 1 | open |
| O8 | **Manual checks after step 1** (§8 list), incl. R16: save a user with a normal e-mail address in the admin (validated by `egulias/email-validator` 4.0.4 since step 1) and check an invalid one is still rejected; Excel exports (R13, zipstream 3) | step 1 | open |

---

## 1. composer.json

### 1.1 Package-by-package (current → per step → final)

`=` means unchanged. "fork" means a pinned in-repo copy under `packages/` (path repository, same package name and namespace, same code, constraint widened, patches listed in §3).

| Package | Now | L9 (PHP 8.1) | L10 (PHP 8.2) | L11 (PHP 8.4) | L12 | **L13 final** | Notes |
|---|---|---|---|---|---|---|---|
| php | ^7.3 | ^8.1 | ^8.2 | ^8.4 | ^8.4 | **^8.4** | |
| laravel/framework | 8.30.1 | ^9.52 | ^10.48 (locks **10.50.3**) | ^11.x | ^12.x | **^13.0** | step 0: 8.83.x. Step 2: 10.49–10.50 are post-EOL security releases (only `unserialize()` guards in the Redis cache/session stores, Forge/Vapor host handling in `TrustProxies`); none applies here (D-S2-4) |
| laravel/tinker | 2.6 | ^2.7 | = (2.11.1 allows L6–L12) | ^2.9 | ^2.10 | **^3.0** | dev tool |
| laravel/ui | 3.2 | ^3.4.6 | **^4.6** (4.6.3) | = | = | **^4.6** | auth traits used by `typicms/users`; byte-identical in 3.4.6 and 4.6.3 (only scaffolding commands/stubs differ); 4.6.3 allows L9–L13. Diffs in §9 R9 |
| guzzlehttp/guzzle | ^7.0.1 | ^7.2 | = | = | = | **^7.8** | |
| fideloper/proxy | ^4.2 | **remove** | | | | | built into Laravel 9 (`Illuminate\Http\Middleware\TrustProxies`) |
| fruitcake/laravel-cors | ^2.0 | **remove** | | | | | built into Laravel 9 (`Illuminate\Http\Middleware\HandleCors`, same `config/cors.php`) |
| bkwld/croppa | 4.11 | **^8.0.1** | = | = | = | **^8.0.1** | 8.0.1 supports L9–L13; URL format and signing token are identical (verified). Image engine changes, see D4 |
| eluceo/ical | ^0.16 | **removed in step 1 (1a2)** | | | | | unused, only required by `typicms/events`. Correction: 0.16.1 allows PHP ≥ 7.1 (`>=7.1 \|\| ~8.0.0`), so it does **not** block PHP 8.1; it was removed because it is unused (D-S1-2) |
| genealabs/laravel-model-caching | 0.11 | ^0.12.5 | **0.13.9** (L10 only) | 11.x / 12.x / 13.x (decide in step 3) | ^12.0 | **^13.1** | used by `Core\Models\Base` (`Cachable`) and `Roles` (`flushCache`). Step 2 correction: the L10 lines are 0.13.x (L10), 11.x (L10–11), 12.x (L10–12); 12.x hashes eager-load constraints into the cache key and rewrites subquery bindings, i.e. can change what a cached query returns in edge cases. 0.13.9 = only the L10 adaptations (D-S2-1). Marked *abandoned* in favour of `mike-bronner/laravel-model-caching` (same author). See R11 |
| laracasts/presenter | 0.2.4 | **^0.2.9** | = | = | = | **^0.2.9** | 0.2.9 allows ~5…^13, same code |
| laracasts/utilities | ^3.2 | ^3.2.5 | = | = | = | **^3.2.5** | |
| laravelium/feed | ^8.0 | **D1** | | | | | abandoned, no L9+ version |
| laravelium/sitemap | ^8.0 | **→ ultrono/laravel-sitemap ^9.5** | ^9.5 | ^10.0 | ^10.0 | **^10.0** | maintained fork: same `app('sitemap')` binding and API, identical config keys and `xml.php` view (diffed). Project uses published views in `resources/views/vendor/sitemap` anyway |
| league/flysystem-aws-s3-v3 | ~1.0 | **^3.0** | = | = | = | **^3.25** | **CRITICAL: production stores all files (documents and images) on Amazon S3** (default disk = `s3` in production; `public` locally). Flysystem 1 → 3 is mandatory from L9 and changes the S3 adapter. See §2.3 (correction: an earlier version of this plan wrongly said "only configured, not used") |
| aws/aws-sdk-php | 3.173.21 | (step 0b: **3.337.3**) | = | = | = | latest 3.x | S3 client. Already updated in step 0b (within constraints, pulled by flysystem-aws-s3-v3 1.0.30). Not exercised by tests or the golden master (dev uses the local disk). Verify against a test bucket before deploying step 0 (§2.3, R19) |
| maatwebsite/excel | 3.1.27 | ^3.1.70 | = | = | = | **^3.1.70** | 3.1.70 allows L5.8…L13 → no need for 4.x-dev used by the reference |
| maatwebsite/laravel-sidebar (TypiCMS fork, VCS) | 1.1.6 | = | = | = | = | **= (pinned commit `09d438e`)** | constraint `illuminate/* >=5.0` already allows L13. **Do not** switch to `typicms/sidebar` 2 (new namespace `TypiCMS\Modules\Sidebar`, no `append()`, would require changing all 13 module composers) |
| msurguy/honeypot | ~1.1.5 | = (1.1.7 allows `9.*`) | **^1.5** (1.5.0) | = | = | **^1.5.0** | not used anywhere in code/views; 1.5.0 allows L4…L13. Step 1: no bump needed (D-S1-8) |
| spatie/eloquent-sortable | ^3.9 | ^4.5 | = (4.5.2 allows `^9.31\|^10\|^11\|^12`) | = | = | **^5.0** | not used by any code (Pages uses its own `SortableSectionTrait`); bump only. Step 2 correction: ^5.0 (PHP 8.2) is only required for L13 (D-S2-2) |
| spatie/laravel-permission | ^3.17 | **^6.25** | = | = | = | **^6.25** | v6 allows L8.12…L13. Same tables/columns, no teams. TypiCMS `Role` must be adapted (§3) |
| spatie/laravel-query-builder | ^3.2.3 | **5.7.0 pinned copy + append patch** | **6.4.4 pinned copy + append patch** (done, 2c/2d) | = | = | **6.4.4 pinned copy** | v5/v6 keep `allowedSorts([...])` arrays and `Filter::__invoke` without return type. **But v5 removed `append=`**, which the admin lists use (`thumb`, `department`, `formatted_year`); QB 3 applied every requested append. Step 1: pinned copy `packages/spatie/laravel-query-builder` 5.7.0 with the QB 3.6.3 append code restored (§3.4). **The append patch must be carried into the 6.x copy in step 2.** (v7 would force changes in 26 files) |
| aws/aws-sdk-php (direct pin) | transitive | **3.337.3** | = | = | = | after O1 | pinned in step 1 (D-S1-5) to keep the S3 client of step 0b until the MinIO tests; forces `flysystem-aws-s3-v3` 3.32.0 (3.34+ need ^3.371.5) |
| spatie/laravel-translatable | ^4.4 | **fork 4.6.0** | = | = | = | **fork 4.6.0** | see §3.3 |
| typicms/* (core, blocks, dashboard, history, menus, roles, search, settings, sitemap, translations, users) | 8.0.x | **fork (exact installed version)** | = | = | = | **fork** | §3 |
| typicms/events | ^9.0 | **removed in step 1 (1a2)** | | | | | unused: never registered in `config/app.php`, only mentioned inside a Blade comment in `pages/public/home.blade.php` (untouched) |
| typicms/objects | ~8.0.0 | **removed in step 1 (1a2)** | | | | | unused: no autoload section, never loaded; its class `Object` is a reserved word (unparsable on PHP ≥ 7.2, also on 7.4) |
| typicms/nestablecollection | 1.1.20 | **fork 1.1.20** | = | = | = | **fork 1.1.20** | v2…v6 rewrote the class (typed, strict); v1.1.20 builds the Pages API tree (`children`, `isLeaf`…) |
| typicms/laravel-translatable-bootforms, typicms/bootforms, typicms/form | 5.0 / 2.0 / 2.0 | = | = | = | = | **=** | constraints (`php>=5.5`, `illuminate/support>=5.8`, `spatie/laravel-translatable>=2.2`) already allow L13. Newer 10.x requires `typicms/translatable` instead of Spatie → would change behavior. Keep. Fork only if a test shows a runtime problem |
| typidesign/laravel-artisan-translations | ~1.0.3 (1.0.4 since step 0b) | = (1.0.4 requires `php ^7.0\|^8.0`) | = (1.0.4, no `illuminate/*` constraint) | ^4.0 if needed | ^4.0 | **^4.0** | artisan commands only. Correction: 1.0.4 accepts PHP 8, so step 1 needs no bump (D-S1-8); it has no framework constraint and its two `Command` subclasses work on L10, so step 2 needs none either (D-S2-3). If a bump is wanted: 3.2 covers L10–L12, 4.0 L10–L13 |
| **new:** symfony/mailgun-mailer + symfony/http-client | — | ^6.x | ^6.x | ^7.x | ^7.x | **^7.4\|^8.0** | **required**: `.env` has `MAIL_MAILER=mailgun`; since L9 (Symfony Mailer) the mailgun transport needs these two packages (the reference has them too) |

**require-dev**

| Package | Now | Final | Notes |
|---|---|---|---|
| phpunit/phpunit | ^9.3 | **^11.5** (L11–L13; L9/L10 keep ^9.5) | L13 requires ^11.5.50 \|\| ^12 \|\| ^13 |
| nunomaduro/collision | ^5.0 | **^8.x** (L9 ^6, L10 ^7) | |
| facade/ignition | ^2.3.6 | **→ spatie/laravel-ignition ^2.x** (L9 ^1.7) | dev error page only |
| fzaninotto/faker | ^1.9.1 | **→ fakerphp/faker ^1.24** | abandoned; not used by the test suite |
| barryvdh/laravel-debugbar | ^3.5 | **^4.x** (L9/L10 ^3.16) | dev only |
| mockery/mockery | ^1.3.1 | ^1.6 | |
| rector/rector + driftingly/rector-laravel | — | optional, dev only | only to *report* (§6) |

### 1.2 Packages with no compatible version, and the alternative

| Package | Why | Alternative chosen |
|---|---|---|
| typicms/* 8.x | constraint `laravel/framework ~8.0`; newer majors change behavior | pinned in-repo copies + minimal patches (§3) |
| spatie/laravel-translatable 4.x | max L8; v5/v6 change stored format and fallback | pinned in-repo copy of 4.6.0 (constraint only) |
| typicms/nestablecollection 1.1.x | max L8; v2+ rewritten | pinned in-repo copy of 1.1.20 (constraint + optional nullable fix) |
| laravelium/feed | abandoned, max L8 | **D1**: you already planned to remove the feed (Stage 2). Otherwise keep a pinned copy of 8.0.1 until then |
| laravelium/sitemap | abandoned, max L8 | `ultrono/laravel-sitemap` (drop-in fork) |
| fideloper/proxy, fruitcake/laravel-cors | superseded by framework | framework middleware, same config |
| eluceo/ical, typicms/events, typicms/objects | unused (they do not block PHP 8.1) | removed in step 1 (D-S1-2) |
| facade/ignition, fzaninotto/faker | abandoned (dev) | spatie/laravel-ignition, fakerphp/faker |

### 1.3 composer.json mechanics

- Add a `path` repository `packages/*/*` **before** Packagist, with the forks keeping their original names and exact versions (`"version": "8.0.11"` etc. in each fork's composer.json). Namespaces don't change, so not a single `use` statement changes anywhere.
- Keep `minimum-stability: dev` and `prefer-stable: true`. Keep the TypiCMS/Laravel-Sidebar VCS repository.
- Commit `composer.lock` at each step.

---

## 2. Laravel structure (8 → 13) and how it's carried over without changing behavior

### 2.1 Decision: keep the Laravel 8 skeleton

Laravel 11 introduced the slim skeleton (`bootstrap/app.php` with `Application::configure()`, `bootstrap/providers.php`, no Kernels), which the reference uses. Laravel 13 **still fully supports the old one**. Verified in v13.34.0:
- `Illuminate\Foundation\Http\Kernel` still reads `$middleware`, `$middlewareGroups` and `$routeMiddleware` (merged with `$middlewareAliases`).
- `Illuminate\Foundation\Console\Kernel::schedule()/commands()` are unchanged.
- `RegisterProviders` reads `config('app.providers')`. All 21 framework providers in our list still exist.
- `Foundation\Support\Providers\RouteServiceProvider::loadRoutes()` still calls `map()`. All 15 module `RouteServiceProvider`s rely on this.
- `Foundation\Exceptions\Handler` still has `render/report/renderHttpException/registerErrorViewPaths/convertExceptionToResponse`.
- `App\Http\Controllers\Controller` traits (`AuthorizesRequests`, `DispatchesJobs`, `ValidatesRequests`) and `Illuminate\Routing\Controller::middleware()` still exist. TypiCMS controllers call `$this->middleware()` in constructors.
- The `token` auth driver (`TokenGuard`, `api_token`) still exists, so `auth:api` keeps working.
- `public/index.php` (Kernel style) and `artisan` work unchanged.
- `EventServiceProvider` auto-discovery (default on since L11) is only active for the framework's own class, not for `App\Providers\EventServiceProvider`. No change.

Migrating to the slim structure would reorder providers (the Pages catch-all route must load last), change middleware priority and exception handling, and touch many files for no technical reason. **Not done.**

### 2.2 Required edits in `app/`, `config/`, `bootstrap/`, `routes/`

| File | Step | Change | Reason |
|---|---|---|---|
| `app/Http/Middleware/TrustProxies.php` | L9 | base class `Fideloper\Proxy\TrustProxies` → `Illuminate\Http\Middleware\TrustProxies`; `$headers = Request::HEADER_X_FORWARDED_ALL` → `Request::HEADER_X_FORWARDED_FOR \| HEADER_X_FORWARDED_HOST \| HEADER_X_FORWARDED_PORT \| HEADER_X_FORWARDED_PROTO` (done, step 1) | fideloper is gone; the constant was removed in Symfony 6; the set is what Symfony 5.4 mapped it to. Note: L9.52's middleware `match`es single constants only, so a combined bitmask falls into its default (adds PREFIX/AWS_ELB); L10+ honours bitmasks. No effect here: no proxy is trusted (`$proxies` unset, `trustedproxy.proxies` null), as before |
| `config/trustedproxy.php` | L9 | `'headers' => Request::HEADER_X_FORWARDED_ALL` → the same FOR\|HOST\|PORT\|PROTO set (done, step 1) | file would fatal on load (undefined constant); L9 reads only `proxies` from it |
| `app/Http/Kernel.php` | L9 | `\Fruitcake\Cors\HandleCors::class` → `\Illuminate\Http\Middleware\HandleCors::class` (same position) | fruitcake removed; framework middleware reads the same `config/cors.php` |
| `app/Exceptions/Handler.php` | L9 | add `invalidJson()` override that returns `['message' => 'The given data was invalid.', 'errors' => …]` | **API contract**: since L9 the 422 `message` is the first error text ("…and N more errors"). `FileManager.vue` shows `error.response.data.message` on upload errors, so the visible text would change. Pins L8 output |
| `config/croppa.php` (+ `config/filesystems.php`) | L9 | rename keys for Croppa 8: `src_dir` (`'filesystem.default.driver'`) → `src_disk` = `config('filesystems.default')` (same disk as now: `s3` in production, `public` locally); `crops_dir` → `crops_disk` = `'croppa_crops'`, a disk in `config/filesystems.php` = the default disk's configuration with `'url' => env('APP_URL')` (same bucket/root, keeps the redirect target identical, §2.3 "Croppa redirect"); **`tmp_disk` = `'local'` (always a local disk, also in production)**; `jpeg_quality` 75 → `quality` 75; `upscale` true → `upsize` true; `path`, `ignore`, `signing_key`, `max_crops`, `memory_limit`, `filters` unchanged | Croppa 8 config format. Same source and crop storage, same redirect target. `tmp_disk` must be local, because Croppa 8 copies a remote (S3) source to `tmp_disk` before decoding it; without it every new thumbnail on S3 fails (verified in Croppa 8.0.1 `Storage::path()`). Configuration only; `FILESYSTEM_DRIVER` alone decides local vs S3 |
| `config/filesystems.php` | L9 | `s3` disk: add `'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)` and **`'throw' => true`** (decided 2026-10-06). No other key changes | `use_path_style_endpoint` defaults to `false`, so production behaves as now; it is only needed for a local S3 emulator (MinIO, D8). `throw` = `true` keeps the Laravel 8 behaviour: a failed S3 write/delete raises an exception (500, nothing saved) instead of silently returning `false` (§2.3). Keys `key`, `secret`, `region`, `bucket`, `url`, `endpoint`, `visibility` and env names (`FILESYSTEM_DRIVER`, `AWS_*`) stay as they are |
| `app/Providers/AppServiceProvider.php` + `app/Filesystem/AwsS3V3Adapter.php` | L9 (done, 1e) | register the `s3` driver: the disk `createS3Driver()` builds, wrapped in a subclass of `Illuminate\Filesystem\AwsS3V3Adapter` whose `url()` turns a `null` path into `''` | **found by the MinIO run (O1)**: folders are `files` rows with `path` NULL and `File::$appends` has `url`; L8 returned `AWS_URL/`, L9 throws a `TypeError` (`PathPrefixer::prefixPath(string)`) that `File::getUrlAttribute()` does not catch (it catches `Exception`) → `/api/files` 500 on S3. The local disk only raises a PHP 8.1 deprecation, so the suite and golden master did not see it. Keeps `Modules/` unchanged. **Check the subclass against `Illuminate\Filesystem\AwsS3V3Adapter` (constructor, `url()`, `createS3Driver()`) in steps 2–5** |
| `config/hashing.php` | L11 | add `'rehash_on_login' => false` | L11+ silently re-hashes and **writes `users.password`** at login when the bcrypt cost differs. Pins L8 behavior (no data writes) |
| `docker/php/Dockerfile` | each step | `php:7.4-apache` → 8.1 / 8.2 / 8.4 image; drop the archived-Debian workaround | test infrastructure, not app code |
| `phpunit.xml` | L11 | migrate to the PHPUnit 10+ schema (`<coverage processUncoveredFiles>` → `<source>`) via `phpunit --migrate-configuration`; `<php>` values unchanged | technical test-config change (§11) |
| `bootstrap/app.php`, `routes/*`, `app/Providers/*`, `app/Console/Kernel.php`, `app/Http/Controllers/Controller.php`, other middleware, `public/index.php`, `artisan`, `server.php` | — | **no change** | verified compatible |
| `.env` / `.env.example` | — | **no change required** | the project's own config files keep reading `CACHE_DRIVER`, `FILESYSTEM_DRIVER` etc. |
| `config/*` (other) | — | **no change required**. From L11, framework defaults are merged for missing keys (e.g. `logging.deprecations` → `null` channel) | |

Optional, not required: renaming `$routeMiddleware` → `$middlewareAliases`, adding the new v6 keys to `config/permission.php` (package defaults are merged automatically), and adding `config/query-builder.php` v6 keys.

### 2.3 Files and Amazon S3 (production)

**Production stores all uploaded files (documents and images) on Amazon S3.** Locally (dev, tests) the default disk is `public` (local). The S3 code path is therefore **not covered by the test suite or the golden master**.

#### How files work now (verified in code, 2026-10-06)

| Topic | Current behaviour |
|---|---|
| Disk selection | `config/filesystems.php`: `'default' => env('FILESYSTEM_DRIVER', 'local')`. Local `.env`, `.env.example`, `.env.testing`, `phpunit.xml`: `FILESYSTEM_DRIVER=public`. Production: `s3` (the production `.env` is not in the repo). Local AWS key/secret/bucket are empty. Every storage call uses the **default disk**; no code names `s3` explicitly |
| S3 → local fallback (or the reverse) | **None exists.** Searched `app/`, `Modules/`, `packages/typicms/*`, `resources/views`, `config/`, `routes/`. The only `try/catch` blocks around storage are `Files\Models\File::getUrlAttribute()` (returns `''` if `Storage::url()` throws), `Files\Observers\FileObserver::deleted()` and Settings `deleteImage()` (log a failed `Croppa::delete()`). None of them switch disks |
| `temporaryUrl()` | **One call site**: `resources/views/vendor/files/public/_documents.blade.php:40`. It sits inside an **HTML** comment `<!-- … -->`, not a Blade comment, so Blade still executes it on every render. On S3 it produces a presigned 5-minute URL that only ends up inside the hidden HTML comment. Locally the `public` disk has no temporary-URL support → `RuntimeException: This driver does not support creating temporary URLs` → HTTP 500 on every page with documents (12 URLs in the golden master). The **visible** download link (line 17) uses `Storage::url()`. Downloads therefore go to the public S3 URL; `temporaryUrl()` has no visible function |
| `Storage::url()` | S3: `AWS_URL` + key if `url` is set, otherwise the S3 client's object URL. Local: `APP_URL/storage/…`. Used by 8 module presenters (`image()` returns the original file, no Croppa), the Files presenter, `File::url`, admin file form, logo/settings views |
| Uploads (`FileUploader`, admin file manager) | `Files ApiController::store` → `FileObserver::creating` → core `FileUploader::handle()`: `Storage::disk(default)->has()` loop for a unique name, then `UploadedFile::storeAs('files', …, default)`. Disk `visibility => 'public'` → object ACL `public-read` |
| Admin file manager | Lists `File` models from the DB. Thumbnails are `thumb_sm` = Files presenter `image()` = `Storage::exists()` + `Storage::url()` (original file from S3, **not** Croppa). Delete: `File::delete()` → `FileObserver::deleted()` → `Croppa::delete('storage/'.path)` (deletes the source and its crops on the Croppa disks, i.e. S3) |
| Croppa (4.11) | `src_dir` and `crops_dir` = `'filesystem.default.driver'` (the Flysystem driver of the default disk → S3 in production). Used only by the core `Presenter::image()` (Announcements, Classschedules, Departments, Newsletters, Tags, Menus, Users, events/objects) and the API `thumb` columns of those models. With S3 as crops disk, Croppa 4 renders the crop, writes it to S3 and answers **301 → `'/'.$cropPath` on the app host** (`url_prefix` is commented out in `config/croppa.php`). How production actually serves that redirect target is **not visible in the repo — to be checked on production (read-only, in a browser)** |
| Existing quirk (unchanged by the upgrade) | core `Presenter::getImagePathOrDefault()` checks `is_file(Storage::path($path))`. On S3 `Storage::path()` returns the object key, so `is_file()` is always false and the core presenter always falls back to `img-not-found.png`, which it copies once to the default disk (S3). Affects only models that use the core presenter (list above). Same in L9–L13 (`path()` = prefixed key there too) |

#### What changes for S3 with Flysystem 3 (Laravel 9+), verified in laravel/framework v9.52.16 and v13.0.0, league/flysystem-aws-s3-v3 3.29, bkwld/croppa 8.0.1

| Area | Laravel 8 / Flysystem 1 (now) | Laravel 9–13 / Flysystem 3 | Effect / action |
|---|---|---|---|
| Package | `league/flysystem-aws-s3-v3 ~1.0` (`AwsS3Adapter`) | `^3.0` (`AwsS3V3Adapter`), wrapped by `Illuminate\Filesystem\AwsS3V3Adapter` | composer change in step 1 |
| Config keys | `key, secret, region, bucket, url, endpoint, visibility` | same keys; new optional `use_path_style_endpoint`, `throw`, `report` (L11+), `temporary_url`, `root`, `options`, `stream_reads` | no rename needed. Add `use_path_style_endpoint` (default false) and explicit `'throw' => false` (§2.2). The whole disk config is still passed to `S3Client` |
| `url()` | `url` config + path prefix, else `getObjectUrl(bucket, key)` | identical logic (`config['url']` + `prefixPath`, else `getObjectUrl`) | same URLs. **Except `url(null)`:** L8 returned the base URL (`AWS_URL/`), L9 throws a `TypeError` (`PathPrefixer::prefixPath(string)`). Found by the MinIO run, fixed in step 1e (§2.2, §14) |
| `temporaryUrl()` | `AwsS3Adapter` → presigned `GetObject` | `AwsS3V3Adapter::temporaryUrl()` → presigned `GetObject` (+ optional `temporary_url` base). Local disks: exception unless a callback is registered (L9+); L11+ local disk supports it only with `'serve' => true` | same on S3. Local behaviour: see D7 |
| `exists()` / `has()` | `doesObjectExist` **or** directory prefix exists | `fileExists` (`doesObjectExistV2`) **or** `directoryExists` (`ListObjectsV2`) | same semantics. **New:** an S3/network error now throws `UnableToCheckFileExistence` instead of returning false (affects the `FileUploader` name loop, presenters) |
| Errors on write/delete/visibility | exceptions propagate (Flysystem 1) | `put`/`putFileAs`/`storeAs`/`delete`/`setVisibility` **catch the error and return `false`** unless `'throw' => true` | without `throw` a failed upload to S3 would no longer raise a 500: `storeAs()` returns `false`, `FileUploader` stores `path = false` and the admin reports success. **Decided: `'throw' => true` on the `s3` disk in step 1** (keeps the L8 behaviour) |
| Visibility / ACL | config `visibility: public` → `ACL public-read` on upload | `PortableVisibilityConverter(config visibility)` → `public-read` on upload; reading visibility needs `GetObjectAcl` | same ACL. If the production bucket uses "Bucket owner enforced" (ACLs disabled), uploads already fail today with an ACL. Check the bucket setting (R18) |
| Mime type / size | `getMimetype`, `getSize` | `mimeType`, `fileSize` (Laravel `mimeType()`/`size()` unchanged) | no project code calls the Flysystem methods directly |
| `getAdapter()->getPathPrefix()` | exists | removed (`path()` instead) | Croppa 4 used it (Croppa 8 replaces it); test change §11 |
| `'filesystem.default.driver'` binding (core) | returns Flysystem 1 `Filesystem` | returns Flysystem 3 `FilesystemOperator` | still resolvable; Croppa 8 no longer uses it (disk names instead) |
| Croppa 8 | — | `cropsAreRemote()` = crops disk is not `LocalFilesystemAdapter`; remote crops: **301 → `Storage::disk(crops_disk)->url($cropPath)`**; a remote source is copied to `tmp_disk` first | the redirect target would change on S3; kept identical by configuration, see "Croppa redirect" below. `tmp_disk` must be local (§2.2). Existing crops already on S3 are reused (`fileExists`) |
| aws/aws-sdk-php | 3.173 (until step 0a) → 3.337.3 (step 0b) | ^3.x | newer SDKs send CRC32 checksums by default (fine on AWS S3). On PHP 7.4 every new S3 client triggers an `E_USER_DEPRECATED` "upgrade to PHP 8.1" notice; Laravel 8.83 routes deprecations to the `deprecations` log channel (`null`) instead of throwing (verified in `HandleExceptions`), so it does not break S3 calls; it disappears with PHP 8.1 in step 1. Verify upload/download against MinIO (O1, R19) |

#### Croppa redirect on S3: what changes in step 1 and how production stays the same

- **Now (Croppa 4.11, `crops_dir` = the S3 driver, `url_prefix` not set):** a request for `/storage/files/photo-x54.jpg?token=…` reaches Laravel (the crop is not in `public/storage`). Croppa renders the crop from the S3 source, writes `files/photo-x54.jpg` to the bucket and answers **`301 Location: /files/photo-x54.jpg`**, a path on the **app host**, not on S3 (`URL::pathToUrl()`: no `url_prefix` → `'/'.$cropPath`). Later requests find the crop on S3 (`has()`) and get the same redirect. Whether that target loads an image in production depends on the production web server; that is O2(c). Note: because of the `is_file()` quirk (§2.3 table), these models currently always point at `img-not-found.png`, so the crop is `img-not-found-x54.png` and the redirect target is `/img-not-found-x54.png`.
- **Croppa 8 (step 1):** same rendering and the same crop key in the same bucket, but the redirect becomes **`301 Location: Storage::disk(crops_disk)->url($cropPath)`**, i.e. the S3 object URL (`AWS_URL` + key, or `https://<bucket>.s3.<region>.amazonaws.com/files/photo-x54.jpg`). There is no `url_prefix` option anymore. So the only change is the redirect target: app host → S3.
- **Implemented in step 1 (D-S1-1, option c; configuration only, same code everywhere):** `config/filesystems.php` defines the `s3` disk once and a disk `croppa_crops` = the same configuration with `'url' => env('APP_URL')`; `config/croppa.php` sets `src_disk` = the default disk, **`crops_disk` = `'croppa_crops'` when `FILESYSTEM_DRIVER` is `s3`, otherwise the default disk**, `tmp_disk` = `'local'`. `FILESYSTEM_DRIVER` is read with `env()` because config files load in name order (`croppa.php` before `filesystems.php`). On S3 crops use the same bucket and keys and `url($cropPath)` = `APP_URL/files/photo-x54.jpg`, the same target as today (absolute instead of relative). Locally (dev, tests) nothing changes: crops on the default disk, served directly, `FileUploadTest` unchanged. A `croppa_crops` disk in every environment was rejected because `FileUploadTest` fakes only `public` and asserts crops there. **To verify on MinIO / test bucket (O1) and against production (O2c) before any deploy.**
- **After O2(c):** if the current target works in production, keep the proposal (identical behaviour). If it turns out the current redirect does not load an image (thumbnails broken today), keeping it identical keeps them broken; switching to the S3 URL would then be a deliberate behaviour change for the owner to approve. Default until then: identical.

#### D7 (applied, commit `e4497d6`)

`App\Providers\AppServiceProvider::boot()` registers `Storage::extend('local', …)`: every disk with the `local` driver is created by `FilesystemManager::createLocalDriver()` as before and gets a `buildTemporaryUrlsUsing()` callback returning the disk's `url()`. The `s3` driver is not touched (L8: the S3 branch of `temporaryUrl()` only runs when no callback is set, and S3 disks never get one; L9+: `Illuminate\Filesystem\AwsS3V3Adapter` overrides `temporaryUrl()`). From Laravel 11 `createLocalDriver(array $config, string $name = 'local')` also takes the disk name, which is only used with `'serve' => true` (not used here); check this call in step 3.

#### Local development and S3 testing (no production bucket, ever)
- Dev keeps the `public` disk. The document pages' 500 is fixed by D7 (applied, `e4497d6`).
- S3 is tested with **MinIO in Docker** (repeatable, offline), plus optionally a **separate test bucket in a non-production AWS account** with the same bucket settings as production (ACL/Object Ownership, Block Public Access) before deploying step 1 (D8). **The production bucket and production credentials are never used for tests and nothing is written to them.**

---

## 3. TypiCMS core packages (`typicms/*`)

### 3.1 Why the core packages cannot be upgraded

| Core major | Laravel | What changes |
|---|---|---|
| 10.x | 9 | new admin UI and Vue components, rewritten presenters/controllers |
| 11.x / 12.x | 10 / 11 | modules merged into `typicms/core`, new migrations (files, translations, pages…), Bootstrap 5/Vue 3 admin |
| 16.x / 17.x | 12 / 13 | single `typicms/core` package containing **Pages, Menus, Files, Users, Roles, Settings, History, Blocks, Translations, Tags, Sitemap…**, `typicms/translatable` instead of Spatie, `typicms/sidebar`, passkeys, new schema |

Any of them would replace the behavior of Pages/Files/Tags (which you already customised in `Modules/`), change slugs/URIs, the history format, translations storage, the API endpoints the Vue admin calls, and require DB migrations. **All violate the constraints.**

### 3.2 Which module depends on which core class (complete matrix)

Counted from the `use` statements of all 183 PHP files in `Modules/`:

| Core class / package | Used by |
|---|---|
| `Core\Models\Base` (+ `Cachable`, `published()`, `whereSlugIs()`, `order()`, `selectFields()`, `uri()`, `editUrl()`, `setStatusAttribute`) | all 17 models of all 13 modules |
| `Core\Http\Controllers\BaseAdminController` (`verified:` middleware, `redirect()`) | all 13 modules (14 controllers) |
| `Core\Http\Controllers\BaseApiController` | all 13 modules (14 controllers; Pages has two) |
| `Core\Http\Controllers\BasePublicController` | 12 modules (Files has no public controller) |
| `Core\Http\Requests\AbstractFormRequest` | all 13 modules (16 requests) |
| `Core\Presenters\Presenter` (extends `Laracasts\Presenter`) | all 13 modules |
| `Core\Facades\TypiCMS` (`getPageLinkedToModule`, `getPagesLinkedToModule`, `mainLocale`, `enabledLocales`) | all route providers/composers (26 uses) |
| `Core\Observers\SlugObserver` | Advertismentboards, Announcements, Banners, Biographies, Categories, Classschedules, Departments, Galleries, News, Newsletters |
| `Core\Filters\FilterOr` | 22 uses: every ApiController + Export |
| `Core\Services\FileUploader` | Files (observer + provider) |
| `History\Traits\Historable` | all models |
| `Menus\Models\Menulink` | Pages (`AddToMenuObserver`, `Page::menulinks`) |
| `TypiCMS\NestableCollection` / `NestableTrait` | Pages |
| `Maatwebsite\Sidebar\SidebarGroup/SidebarItem` + view `core::admin._sidebar` | all 13 `SidebarViewComposer`s |
| `Spatie\Translatable\HasTranslations` | all models except Tag |
| `Spatie\QueryBuilder\QueryBuilder/AllowedFilter` | 12 modules (ApiController + Export), Pages sections |
| `Laracasts\Presenter\PresentableTrait` | all models |
| `Maatwebsite\Excel` | 10 modules (export) |
| core middleware groups `public` / `admin` (SetLocale, PublicAccess, AdminLocale, JavaScriptData, UserPrefs, SetTranslatableFallbackLocaleToNull) | every module route |
| core views (`core::admin.master`, `core::public.master`, `_feed-links`, form partials) | all module views |

The core packages also provide modules that are **not** in `Modules/`: Users (login, register, password, verify), Roles, Settings, History, Menus, Blocks, Dashboard, Search, Sitemap, Translations.

### 3.3 Strategy: pinned in-repo copies

- Copy the **exact installed** versions (`core 8.0.11`, `blocks 8.0.0`, `dashboard 8.0.0`, `history 8.0.1`, `menus 8.0.2`, `roles 8.0.1`, `search 8.0.0`, `settings 8.0.0`, `sitemap 8.0.0`, `translations 8.0.0`, `users 8.0.6`, `nestablecollection 1.1.20`, `spatie/laravel-translatable 4.6.0`) into `packages/<vendor>/<name>`. Do this in **step 0, on Laravel 8 / PHP 7.4, before anything else changes**, and confirm with the full test suite that nothing changed.
- From then on, every patch is a small, reviewable diff in our own repo, listed below. No copied code is "improved".
- Each copy gets a `PATCHES.md` listing what was changed against the original and why.

### 3.4 Required patches inside the pinned copies

| Package | File | Step | Patch | Reason / behavior kept |
|---|---|---|---|---|
| all `typicms/*` | `composer.json` | 0 | widen `laravel/framework ~8.0` → `^8.0\|…\|^13.0` | install only |
| `spatie/laravel-translatable` 4.6 | `composer.json` | 0 | widen `illuminate/*` to ^13 | install only. Code verified against L13: `getCasts()`, `asJson()`, `hasGetMutator()`, `mutateAttribute()` unchanged. **Stored format stays `json_encode` without flags (`\uXXXX`), empty values stay dropped, fallback stays as now** (all pinned by `TranslatableStorageTest`) |
| `typicms/nestablecollection` 1.1.20 | `composer.json` | 0 | widen constraint | install only. `Model::newCollection()` signature in L13 is unchanged (no return type) |
| `typicms/roles` | `src/Models/Role.php` | L9 | implement Permission v6 contract signatures: `findByName(string $name, ?string $guardName = null): self`, `findById(int\|string $id, ?string $guardName = null): self`, `findOrCreate(string $name, ?string $guardName = null): self`, `hasPermissionTo($permission, ?string $guardName = null): bool`; inside `hasPermissionTo` call `$permissionClass::findByName()/findById()` statically (v6 `getPermissionClass()` returns a class-string, v3 returned an instance) | fatal without it (incompatible declaration / call on string). Logic and queries unchanged. Covered by `AdminAccessTest::test_permissions_are_enforced_per_action` and `NewsApiTest::test_api_enforces_permissions` |
| `typicms/users` | `src/Models/User.php` | L9 (done) | override `getDefaultGuardName()`: the first guard of the model (`Guard::getNames($this)->first()`), i.e. the Permission 3 logic | **found in step 1:** Permission 6 `Guard::getDefaultName()` prefers the guard of the current request; on the admin API (`auth:api`) that is `api`, but all permissions/roles use `web` → every non-superuser got 403 (`NewsApiTest::test_api_enforces_permissions`). `getGuardNames()` unchanged (web, api) |
| `typicms/sitemap` | `composer.json` | L9 (done) | `laravelium/sitemap ~8.0` → `ultrono/laravel-sitemap ^9.5` | no laravelium release for L9; ultrono identical apart from the namespace; the package only uses `app('sitemap')` |
| `spatie/laravel-query-builder` 5.7.0 (new pinned copy) | `src/Concerns/AppendsAttributesToResults.php` (new, verbatim from 3.6.3), `src/QueryBuilder.php` (`use` + `__call()` hooks from 3.6.3), `composer.json` (`version`) | L9 (done) | restores the Query Builder 3 `append=` behaviour (appends every requested attribute to a returned Model / Collection / paginator) | **found in step 1:** QB 5 removed appends; the admin lists lost `thumb`, `department`, `formatted_year`. Pinned by `tests/Feature/Api/AppendsTest.php`. **Step 2 (2c/2d): the copy is now 6.4.4 (verbatim, reference `ab9c4c3`) with the same patch re-applied by hand** (trait byte-identical, `__call()` hooks identical; `PATCHES.md`) |
| `typicms/translations` | `src/Loaders/MixedLoader.php` | L10 (done, 2e) | `$this->loadPath($this->path, …)` → `$this->loadPaths($this->paths, …)` | `FileLoader` replaced `$path/loadPath()` with `$paths/loadPaths()` in L10. Constructor still receives the single `resources/lang` path, so **framework-shipped lang files are still not loaded**, same as today |
| `typicms/core` | `src/Presenters/Presenter.php` (`dateLocalized`, `dateTimeLocalized`), `src/Services/Dates.php` | L11 | replace `$carbon->formatLocalized($fmt)` with a local helper that does exactly what Carbon 2 did: `strftime($fmt, strtotime($carbon->toDateTimeString()))` | `formatLocalized()` was removed in Carbon 3 (L11 allows Carbon 2/3, L12+ requires 3). `strftime()` still exists in PHP 8.4 (deprecated only), so output stays byte-identical, including the `setlocale()` done by `SetLocale`. All 5 modules that render `dateLocalized` in views override it with `format('d.m.Y')`, so public output does not even pass through here |
| `typicms/core` | `src/Models/Base.php` | — | none required. `int $category_id = null` is a PHP 8.4 deprecation only | optional (§6) |
| `typicms/core` | `src/Filters/FilterOr.php` | — | none required. The QB v5/v6 `Filter` interface declares `__invoke(Builder $query, $value, string $property)` (v6: `mixed $value`) with **no return type**, so the existing implementation with `: Builder` stays valid. (QB v7 would require `: void`, one reason v7 is not used) | — |
| `typicms/core` | `src/Providers/ModuleServiceProvider.php` | — | none. `'filesystem.default.driver'` binding still works (`FilesystemAdapter::getDriver()` exists); it is still resolved by `ApplicationBootTest` | — |
| `typicms/users` | — | — | none required. `laravel/ui` 4 auth traits work with the same controllers (byte-identical to 3.4.6, verified in step 2) | optional behavior pins in R9 |
| `typicms/sitemap` | — | — | none. Uses only `app('sitemap')`, which `ultrono/laravel-sitemap` binds identically | — |
| `typicms/history`, `menus`, `blocks`, `dashboard`, `search`, `settings` | — | — | none found by lint / Rector / API review | no automated coverage → manual check list in §8 |

### 3.5 Behavior-sensitive areas and how each is preserved

| Area | Risk | How it's kept |
|---|---|---|
| **Routing** (DB-driven module routes, locale prefixes, catch-all last) | provider order, `map()` | same skeleton, same provider list and order; module `RouteServiceProvider`s untouched; `ApplicationBootTest::test_critical_named_routes_are_registered`, `PublicRoutesTest` |
| **Slugs / URIs** (`SlugObserver`, `UriObserver`, `whereSlugIs`, `whereUriIs`) | translatable getter returning `null` instead of `''` (Spatie 6) → `TypeError` in `UriObserver::incrementWhileExists(string $uri)` | Spatie 4.6 pinned; L13 JSON path compilation `json_unquote(json_extract(col, '$."sr-latn"'))` unchanged; `TranslatableQueryTest` |
| **History** (`Historable`) | reads protected `$model->attributes/original` from closures | unchanged code; no L13 change to those properties; `NewsAdminCrudTest`, `NewsModelTest` |
| **Croppa** | URL format, `?token=` (md5(app.key + basename)), route `{path}` pattern, delete | Croppa 8 `URL::generate()` / `signingToken()` diffed: identical; route still registered in `boot()` (before the Pages catch-all, as now); `FileUploadTest`. **Pixel output of newly generated thumbnails changes** (PHPThumb → Intervention 3 GD), see D4/R4 |
| **Translations** (DB + file `MixedLoader`, `__()`, admin `TranslatableBootForm`) | FileLoader API | patch above; `AuthenticationTest` asserts `sr` DB translations; `TranslatableStorageTest` |
| **API** (`auth:api` token, QueryBuilder, paginator JSON, `selectFields` raw SQL) | QB/paginator/serialization changes | sorts/filters/includes/fields behave the same for our calls; **`append=` restored by the QB patch (§3.4)**; `LengthAwarePaginator::toArray()` keys identical in L13; **`per_page` is an integer from L9 on (accepted, §7)**; date serialization unchanged; 422 message pinned in Handler; `NewsApiTest`, `PagesAndClassschedulesApiTest`, `FileUploadTest`, `ValidationMessageTest`, `AppendsTest`, golden master |
| **Model caching** (`Cachable`) | cache-key/invalidation implementation | same trait name and API (`flushCache`) in 13.x; only affects caching, not results; whole suite runs with array cache (taggable) → caching active in tests |
| **Permissions** | v6 internals | same tables (`role_user`, `permission_user`, `permission_role`, `model_id`), same `Gate` check registration (merged default `register_permission_check_method=true`); `AuthServiceProvider` `Gate::before` superuser unchanged |
| **Sidebar** | package | identical code (VCS fork kept) |

---

## 4. Modules — required changes, per module

Only changes without which the code does **not run** on PHP 8.4 / Laravel 13. Everything else is "no change".

| Module | Required change | File(s) | Step | Reason |
|---|---|---|---|---|
| **Advertismentboards** | `protected $dates = ['date'];` → `protected $casts = ['date' => 'datetime'];` | `Models/Advertismentboard.php` | L10 | `$dates` removed in L10 (`getDates()` now returns timestamps only), so `$model->date` would become a string and `->format()` in `dateLocalized` would fatal. `'datetime'` gives the same Carbon instance, the same DB write format (`fromDateTime`) and the same JSON (`serializeDate`) |
| **Announcements** | `protected $dates = ['date', 'expiry'];` → `protected $casts = ['date' => 'datetime', 'expiry' => 'datetime'];` | `Models/Announcement.php` | L10 | same. `AnnouncementCategory.php`: no change |
| **Banners** | none | — | — | — |
| **Biographies** | none | — | — | — |
| **Categories** | none | — | — | — |
| **Classschedules** | `$dates` → `$casts` with `'datetime'` for `from_date`, `to_date`, `expiry` | `Models/Classschedule.php` | L10 | same |
| | `$dates` → `$casts` with `'datetime'` for `from_date`, `to_date` | `Models/ClassScheduleItems.php` | L10 | same. The class's `protected $relations = [...]` override (known issue #11) is still a legal redeclaration in L13 (property is untyped there), so no change |
| **Departments** | none | — | — | — |
| **Files** | none | — | — | `Croppa::delete()` signature in Croppa 8 is compatible; `FileUploader` → `Storage::disk()->has()` is forwarded to Flysystem 3 (verify: `FileUploadTest::test_uploading_the_same_file_name_twice_does_not_overwrite`) |
| **Galleries** | none | — | — | — |
| **News** | `protected $dates = ['date'];` → `protected $casts = ['date' => 'datetime'];` | `Models/News.php` | L10 | same. The `feed()` action depends on **D1** (not part of this upgrade's changes) |
| **Newsletters** | none | — | — | — |
| **Pages** | none | — | — | `UriObserver` implicit nullable `int $id = null` is a deprecation only (optional fix §6) |
| **Tags** | none | — | — | — |

Notes:
- **Step 2 (done, commit 2b `ac2b8cd`):** the 5 casts were applied on Laravel 9 before the framework changed, pinned by the new `Models/DateCastsTest` (2t). It is 5 models in 4 modules (Classschedules has two). Read-only check on all dev DB rows (1,491 rows, 1,972 date values, 8 write inputs per column, dirty check): 0 differences between `$dates` and the casts. `DateCastsTest` fails on Laravel 10 without 2b.
- Nothing else in any module changes: routes, URLs, validation rules, views, controllers, query-builder calls, exports, observers, presenters, providers.
- Existing quirks are **kept unchanged**, e.g. `Newsletters AdminController::update` logs an undefined `$message`, the `TypiCms\…\Tag` import casing, `AnnouncementCategory::exam()` pointing to a non-existent class, and the protected `updatePartial/move` controller actions (still dispatchable through `Controller::callAction`). They behave the same on PHP 8.4 (notices and warnings are converted to exceptions in both versions).

---

## 5. Database (analysis only)

**No database change is required at any step.** Nothing in `database/` is touched, no package migration is published or run, `migrate` is never run, and neither `akademija` nor `akademija_testing` is altered (the test bootstrap keeps rebuilding `akademija_testing` from the schema file as it does today).

Checked:

| Item | Result |
|---|---|
| Spatie Permission 6 | same 5 tables/columns as v3 with our `table_names` (`role_user`, `permission_user`, `permission_role`) and `model_morph_key=model_id`; teams disabled → `add_teams_fields` not needed |
| Password resets | `config/auth.php` keeps `password_resets` (L10+ default `password_reset_tokens` only applies to new skeletons) |
| Translations | Spatie 4.6 pinned → identical JSON in all translatable columns |
| JSON SQL | L13 MySQL grammar compiles `col->locale` and `whereJsonContains` the same way; `TranslatableQueryTest` covers it. Keep `DB_CONNECTION=mysql` (do **not** switch to the L11 `mariadb` driver; `ApplicationBootTest` pins `mysql`) |
| Queue / failed jobs / cache / sessions | unchanged drivers (`sync`, `file`), no new tables |
| Runtime data writes introduced by the upgrade | only L11 `rehash_on_login` → pinned off (§2.2) |
| `tests/Fixtures/BaselineSeeder.php` | **no change needed**. PHP 8.4 only emits a deprecation for `page(…, array $status = null)` |

If the tests reveal anything that needs a schema or data change, I stop and report it.

---

## 6. PHP 7.4 → 8.4 changes in `app/` and `Modules/`

### 6.1 Results of the automated checks

- **PHP 8.4 lint**: 0 errors in `app/`, `Modules/`, `config/`, `routes/`, `database/`, `tests/`. Deprecations only:
  - `Modules/Pages/Observers/UriObserver.php`: `int $id = null` (×2): implicit nullable
  - `tests/Fixtures/BaselineSeeder.php`: `array $status = null`: implicit nullable
  - vendor copies: `Core\Models\Base` (×3), `TranslatableBootForm` (×3), `NestableCollection` (×2), `MixedLoader`, Sidebar `Itemable`, Spatie `HasTranslations` (×2)
  - `typicms/objects`: **fatal** (`class Object`, reserved), never loaded; removed in step 1 (D-S1-2)
- **Rector dry-run** (compatibility rules, `ExplicitNullableParamTypeRector`, `NullToStrictStringFuncCallArgRector`, `VariableInStringInterpolationFixerRector`, `ClassOnObjectRector`, `RemoveGetClassGetParentClassNoArgsRector`):

| File | Rule | What it would do | Required? |
|---|---|---|---|
| `Modules/Pages/Observers/UriObserver.php` | ExplicitNullableParamTypeRector | `int $id = null` → `?int $id = null` (2×) | no (8.4 deprecation; becomes required only in PHP 9) |
| `Modules/Pages/Presenters/ModulePresenter.php` | NullToStrictStringFuncCallArgRector | `explode('/', (string) $parentUri)` | no (value is never null here) |
| `Modules/Biographies/Providers/ModuleServiceProvider.php` | NullToStrictStringFuncCallArgRector | `explode('/', (string) $route->uri)` | no |
| `Modules/Files/Http/Controllers/ApiController.php` | NullToStrictStringFuncCallArgRector | `explode(',', (string) $ids)` | no |
| `app/helpers.php` | NullToStrictStringFuncCallArgRector | `(string)` casts in `mb_ucfirst` | no (see 6.2) |
| vendor copies (core Base, Presenter, TypiCMS service, translations, menus, blocks, sitemap, history, form/bootforms) | same rules | casts / `?type` | no |

No `${var}` interpolation, `each()`, `create_function`, `utf8_encode`, `get_class()` without arguments, `FILTER_SANITIZE_STRING`, dynamic-property assignment on our own classes, or other removed APIs were found.

**Proposal:** apply **none** of these in `Modules/` and `app/`. They are not technically required and the policy is "no changes beyond what's required". Deprecations go to the `deprecations` log channel (`null` by default) and do not affect behavior. If you'd rather have a clean deprecation log, approve D6 and only `ExplicitNullableParamTypeRector` is applied (behavior-neutral).

### 6.2 PHP 8.x semantic changes reviewed

| Change | Where it could matter | Verdict |
|---|---|---|
| String↔number comparison (PHP 8.0) | `$category_id==0`, `$tag_id==0`, `$program_id==3`, `$this->year == 1` | inputs are `null`/numeric strings (`ConvertEmptyStringsToNull` turns `''` into `null`), so results are the same |
| Warnings instead of notices (undefined index/variable) | Newsletters `$message`, Biographies `$view->page`, `Historable` `$model->original[$key]` | Laravel already converted notices to exceptions, so the outcome is the same 500 |
| `TypeError` for internal functions | `Announcement::getFormattedProgramAttribute` with `program = null` (known issue #8) | was a warning→exception, now a TypeError→exception: the same 500 |
| Deprecation: null to non-nullable internal parameter (8.1) | several `json_decode/explode/mb_*` | logged only |
| Float-to-int implicit conversion (8.1) | `Files` presenter `$suffixes[floor($base)]` | `floor()` is integral, no deprecation |
| **New native `mb_ucfirst()` in PHP 8.4** | `app/helpers.php` defines it only `if (!function_exists(...))`, so on 8.4 PHP's native one is used | only caller is `typicms:create` (artisan scaffolding). Native uses title case: differs only for single-codepoint digraphs (ǆ/ǉ/ǌ). No runtime effect. No change proposed |
| `strftime()` deprecated (8.1) | core `formatLocalized` replacement (§3.4) | still works on 8.4; removed in PHP 9, a future concern only |

---

## 7. Frontend, Blade and the API contract

- **Untouched**: `resources/js`, `resources/scss`, Vue components, `package.json`, `package-lock.json`, `yarn.lock`, `webpack.mix.js`, `public/` assets.
- **Blade**: no change is required. Checked: published pagination views (`Paginator::useBootstrap()` still maps to `bootstrap-4`), mail/notification views (`@component('mail::…')` still supported), sidebar views, `@php`/`{{ }}`/`{!! !!}` semantics. If a test shows a Blade incompatibility, I'll stop and list it before changing anything.
- **API contract** (routes, parameters, JSON, auth):
  - Routes and middleware: defined in unchanged module providers.
  - Auth: `auth:api` token guard (`api_token`, `Bearer`, `hash=false`) is unchanged in L13.
  - Query parameters `sort`, `filter[...]`, `fields[table]`, `include`, `per_page`, `locale`: QB 6.4 parses them the same way for our usage (diffed). Unknown sort/filter/include still → 400. One SQL difference (step 2): with `fields[relation]` + `include` QB 6 selects qualified columns (`roles.name` instead of `name`); only the Users admin list sends this, and its JSON is identical (golden master `/api/users?…&fields[roles]=name&include=roles`).
  - `append=` (used by the admin lists for `thumb`, `department`, `formatted_year`): Query Builder 5+ removed it; kept by the pinned-copy patch (§3.4).
  - Paginated JSON keys are identical (`current_page, data, first_page_url, from, last_page, last_page_url, links, next_page_url, path, per_page, prev_page_url, to, total`). **Type change accepted (step 1, decision 2):** `per_page` is an integer from Laravel 9 on (`"100"` → `100`, `LengthAwarePaginator` casts it); the only consumer, the admin Vue, uses `parseInt(data.per_page)` and string concatenation.
  - `*_translated` columns come from `Base::selectFields()` raw SQL, unchanged.
  - Model JSON: translatable fields via Spatie 4.6 (unchanged); `datetime` casts serialize exactly like `$dates`.
  - 422 `message` is pinned (§2.2). 401 `Unauthenticated.`, 403 `This action is unauthorized.` and custom 403 messages are unchanged.
- Nothing in this upgrade **requires** touching the frontend.

---

## 8. Order of steps, and which tests confirm each

**Why one major at a time instead of a direct jump.** The breaking changes are spread across versions:
- L9: Flysystem 3, Symfony 6 Mailer, proxies/CORS built in
- L10: `$dates` removed, `FileLoader` paths
- L11: Carbon 3 allowed, PHPUnit 10+, config merging, rehash on login
- L12: Carbon 3 required
- L13: PHP ≥ 8.3, Symfony 7.4/8

Several dependencies also only bridge specific ranges (QB 5.7 is L9–L10 only, genealabs changes major every one or two Laravel versions, `typidesign` per major). Stepping isolates every failure to one known set of changes, and the full suite runs between steps.

Branches: one per step, created from `development` (`upgrade/step-0` merged; `upgrade/step-1` from `development`, D10). One commit (or a few) per step. **After each step the whole suite must pass before moving on. Tests are not changed to pass, except the technical test changes listed in §11.**

| Step | PHP | Laravel | Work | Confirmed by |
|---|---|---|---|---|
| **0 – Preparation** (done) | 7.4 | 8.30 → **8.83** | create the `packages/` pinned copies (§3.3) from the installed vendor code + path repositories; `typicms/events`, `typicms/objects`, `eluceo/ical` kept (D2) and removed in step 1; `composer update` within L8 (laravel/framework 8.83, laravel/ui 3.4, genealabs 0.11.7, facade/ignition 2.17, …) with all typicms/Spatie-translatable/nestable versions **pinned exactly** | **full suite, identical results** (proves the copies are byte-for-byte the same code), esp. `Boot/*`, `Infrastructure/*` |
| **1 – Laravel 9** (done, §14) | **8.1** | **9.52** | Dockerfile PHP 8.1; composer per §1 (L9 column); remove fideloper/fruitcake; Kernel CORS + TrustProxies + `config/trustedproxy.php`; `Handler::invalidJson`; Croppa 8 + `config/croppa.php` (local `tmp_disk`); **Flysystem 3 + `league/flysystem-aws-s3-v3` ^3.0 (production S3, §2.3)** + `config/filesystems.php` (`use_path_style_endpoint`, `throw`); ultrono sitemap; symfony mailgun/http-client; Permission 6 + `Role` patch + `User` default-guard patch; QB 5.7 pinned copy + append patch; Excel 3.1.70; presenter 0.2.9; typidesign unchanged (1.0.4); **D1 resolved before this step** | full suite **+ S3 checks against MinIO/test bucket (below)**. Key: `Images/FileUploadTest` (Flysystem 3, Croppa, local disk only), `Admin/AdminAccessTest` + `Api/NewsApiTest::test_api_enforces_permissions` (Permission 6), `Api/*` (QB 5, paginator), `Auth/*` (laravel/ui), `Boot/*` (providers, `feed`/`excel`/`filesystem.default.driver` resolution) |
| **2 – Laravel 10** (done, §15) | **8.2** | **10.50.3** | Dockerfile PHP 8.2; `$dates` → `$casts` in the 5 module models; `MixedLoader::loadPaths`; QB 6.4.4 pinned copy + append patch; genealabs 0.13.9 (D-S2-1); laravel/ui ^4.6; honeypot ^1.5; collision 7; ignition 2; transitive Monolog 3, `laravel/prompts`, `carbon-doctrine-types` 2.x; Symfony stays 6.4 (no `-W`); eloquent-sortable and typidesign unchanged (D-S2-2/3) | `Models/NewsModelTest`, `Models/ClassscheduleTest` (casts), `Translations/*` + `Auth/AuthenticationTest` (DB translations through `MixedLoader`), `Api/*` (QB 6), `PublicRoutesTest` (dates in views); **S3: MinIO run (`tools/s3-test`, O1) + check `App\Filesystem\AwsS3V3Adapter` against the framework class (R22)** |
| **3 – Laravel 11** | **8.4** | **11.x** | Dockerfile PHP 8.4; core `formatLocalized` compat; `hashing.rehash_on_login=false`; PHPUnit 11 + `phpunit.xml` migration; collision 8; ultrono 10; typidesign 4; tinker 2.9 | full suite under PHPUnit 11. Key: `Auth/*` (rehash), `Boot/*` (config merging), `Translations/*`; **S3: MinIO run (`tools/s3-test`, O1) + check `App\Filesystem\AwsS3V3Adapter` against the framework class (R22)** |
| **4 – Laravel 12** | 8.4 | **12.x** | framework + Carbon 3 (required); genealabs 12/13 | full suite. Key: `Models/*`, `PublicRoutesTest` (Carbon 3 dates), `Translations/TranslatableQueryTest`; **S3: MinIO run (`tools/s3-test`, O1) + check `App\Filesystem\AwsS3V3Adapter` against the framework class (R22)** |
| **5 – Laravel 13** | 8.4 | **13.x** | framework; tinker 3; genealabs 13.1; Symfony 7.4/8; final `composer.json` cleanup (`php: ^8.4`) | full suite + manual checks below; **S3: MinIO run (`tools/s3-test`, O1) + check `App\Filesystem\AwsS3V3Adapter` against the framework class (R22)** |

**Manual verification after steps 1 and 5** (areas without automated coverage; read-only against the dev DB `akademija`, nothing is written):
- Admin smoke test per module: list, edit form, save (on the testing DB only), Excel export of News/Banners/Users (open the `.xlsx`, compare column formats)
- `/sitemap.xml` output (diff against a capture taken before step 0), search page, menus (`@menu`), blocks (`@block`), history list, settings page, translations admin, users admin list (`allowedFields`)
- password reset / registration / email-verification pages render
- Contact form mail on the `array`/`log` mailer
- a few thumbnails in the admin file manager

**S3 checks after every step** (D8: against MinIO with `tools/s3-test/` — `bash tools/s3-test/run.sh <label>`, compare with the `old-code` reference — and, before deploying, a separate non-production test bucket with the production bucket settings; **never the production bucket or production credentials**). Reference run on the pre-upgrade code and the step 1 run done 2026-10-22, step 2 run 2026-10-29 (`tools/s3-test/S3_TEST_REPORT.md`). **In steps 2–5 also check `App\Filesystem\AwsS3V3Adapter` against the framework's `Illuminate\Filesystem\AwsS3V3Adapter` (R22); `Api/FilesS3UrlTest` covers it.** The list:
- upload an image and a PDF in the admin file manager → object exists in the bucket under `files/`, ACL `public-read`, correct `Content-Type`; a second upload with the same name gets `_1`
- the uploaded file opens through its `Storage::url()` link (admin file form, public page `_documents` link)
- open a document via a **temporary URL** (`Storage::temporaryUrl()`, presigned, expires after 5 minutes; check it fails after expiry)
- an image through **Croppa** (core presenter / API `thumb`): first request renders the crop, stores it in the bucket and answers 301; note the redirect target (Croppa 4: `/<cropPath>` on the app host; Croppa 8: S3 object URL); second request reuses the stored crop
- admin file-manager thumbnails (`thumb_sm`, original S3 URL)
- delete the file in the admin → source object and all its crops are removed from the bucket
- simulate an S3 failure (stop MinIO / wrong credentials) during upload → compare with the reference behaviour (L8: exception, nothing saved; L9 with `throw => false`: silent `false`), see §2.3

Optional: before step 0, capture the HTML/JSON/XML of a fixed list of URLs (public pages per locale, `/api/*` lists with sort/filter/fields, `sitemap.xml`) from the dev DB, then diff after every step. Read-only. Needs your approval (D5).

---

## 9. Risk register: where behavior could change, and the mitigation

| # | Risk | Where | Mitigation | Covered by |
|---|---|---|---|---|
| R1 | Upgrading typicms/* rewrites modules, DB and API | §3 | don't upgrade; pinned copies | entire suite |
| R2 | Spatie Translatable 6 stores unescaped JSON, returns `null`, different fallback | all translatable models | pinned 4.6.0 | `Translations/TranslatableStorageTest` (7 tests), `TranslatableQueryTest` |
| R3 | API JSON/params change (QB 5+ removed `append=`, QB 7, validation message, `per_page` type) | Vue admin | QB 5.7 pinned copy with the append patch (6.x in step 2); `Handler::invalidJson` pin; `per_page` int accepted | `Api/*`, `ValidationMessageTest`, `AppendsTest`, `FileUploadTest`, golden master |
| R4 | **Newly generated** Croppa thumbnails are encoded by Intervention 3 instead of PHPThumb (sharpness/compression may differ slightly; same size and crop). Existing crops on disk are served unchanged by the web server | admin file manager thumbs, core-presenter `thumb` (Departments, Classschedules, Newsletters, Tags, Users…). Public module views mostly use original files | quality 75 and upsize kept; optionally keep a PHPThumb-based copy of Croppa 5.1 if you need byte-identical new thumbnails (D4) | `FileUploadTest` (URL, token, generation, delete) |
| R5 | `formatLocalized` removed (Carbon 3) | core Presenter / Dates | strftime-based compat inside the core copy | `PublicRoutesTest` (module overrides), manual |
| R6 | Password re-hash on login writes the DB (L11) | users | `rehash_on_login=false` | `Auth/*` |
| R7 | Mailgun transport needs extra packages (L9 Symfony Mailer) | contact form, notifications | add `symfony/mailgun-mailer` + `symfony/http-client`; same `services.mailgun` keys | manual (tests use the `array` mailer) |
| R8 | Feed package missing → `_feed-links` (in **every** public page `<head>`) calls `app('feed')` | public layout | D1 | `PublicRoutesTest`, `ApplicationBootTest` |
| R9 | laravel/ui 4 trait details: `remember` read with `boolean()` instead of `filled()`; password reset rule `Password::defaults()` instead of `min:8` (same minimum, message key may differ); throttle key transliterated (same for ASCII emails); session `auth.password_confirmed_at` set at login. **Correction (step 2):** these are already in laravel/ui 3.4.6, i.e. they arrived with step 0b; the ui 4.6.3 auth traits are byte-identical to 3.4.6, so ui 4 changes nothing at runtime | login, reset | no visible effect for the current forms. If you want exact pins, override `rules()` in `ResetPasswordController` and `attemptLogin()` in `LoginController` inside the users copy (D3) | `Auth/*` (login, failures, logout) |
| R10 | Permission 6 internals (cache format, Gate registration, **default guard**) | admin authorization | same tables; defaults merged; `Gate::before` superuser unchanged; **default guard: `User::getDefaultGuardName()` patch (§3.4)**; **the cache is NOT rebuilt automatically: a cached Permission 3 entry breaks v6 → `permission:cache-reset` on deploy (O6)** | `Admin/AdminAccessTest`, `NewsApiTest::test_api_enforces_permissions` |
| R11 | genealabs model caching is abandoned upstream; implementation changed between 0.11 and 13 | all `Base` models | same trait API; results not affected (cache only). Fallback: pinned copy of 0.11.x. Step 2: 0.13.9 (cache keys change → `cache:clear` on deploy, O6). Step 3+: choose 11/12/13 with the same diff; 12.x+ changes cache keys for eager-load constraints and subqueries; consider `mike-bronner/laravel-model-caching` (the successor) | entire suite (array store = caching active) |
| R12 | Sitemap via ultrono | `/sitemap.xml` | identical config keys and views (diffed); project uses published views | manual diff |
| R13 | Excel 3.1.27 → 3.1.70 (PhpSpreadsheet 1.x newer; since step 1 `maennchen/zipstream-php` 3 as xlsx writer) | exports | same major API; same column formats | manual (O8) |
| R14 | Flysystem 3 semantics on **S3 in production**: `put`/`storeAs`/`delete` return `false` instead of throwing; `exists()` throws on S3 errors | `FileUploader` (a failed upload would save `path = false` and report success), Croppa, `FileObserver::deleted` | **`'throw' => true` on the `s3` disk (decided; applied in step 1)**; O1 | `FileUploadTest` (local disk only) + O1 |
| R15 | Validation messages (L9+ wording) | admin forms | project's own `resources/lang/*/validation.php` keep being used (MixedLoader loads only the project path) | `NewsAdminCrudTest::test_invalid_admin_input_is_rejected_and_nothing_is_saved` |
| R16 | `email:rfc,dns` validation (egulias v2 → **4.0.4** since step 1) in Users admin/register | users | same rule, newer validator; edge cases only | manual (O8): save a user with a normal e-mail in the admin; an invalid one is still rejected |
| R17 | Non-covered TypiCMS modules (menus, blocks, search, history, settings, translations, dashboard) | admin/public | unchanged pinned code; manual list in §8 | manual |
| R18 | S3 adapter changes (Flysystem 3, `league/flysystem-aws-s3-v3` 3.x): ACL/visibility handling, `has()` via `HeadObject`/`ListObjectsV2`, URL building | all uploads, downloads, image URLs in production | code diffed (§2.3): same `url()`, same `public-read` ACL, same `has()` semantics. Check the production bucket settings (Object Ownership / ACLs enabled, Block Public Access) read-only and reproduce them in the test bucket | S3 checks §8 |
| R19 | `aws/aws-sdk-php` 3.173 → 3.337.3 was already pulled in by step 0b (default CRC32 checksums, newer signing defaults; `E_USER_DEPRECATED` notice on PHP 7.4, logged to `null`, not thrown) | every S3 call in production | **pinned at 3.337.3 in step 1** (D-S1-5) until O1; O1 before deploying | O1 |
| R20 | Thumbnails on S3: Croppa 8 redirects to `Storage::disk(crops_disk)->url()` (S3 URL) instead of `/<cropPath>` on the app host; needs a local `tmp_disk` | core-presenter images (Announcements, Classschedules, Departments, Newsletters, Tags, Menus, Users), API `thumb` columns | configuration-only `croppa_crops` disk with `url = APP_URL` keeps the target identical (§2.3 "Croppa redirect"); `tmp_disk => 'local'`; O2(c) | O1, O2 |
| R21 | `_documents.blade.php` executes `Storage::temporaryUrl()` inside an HTML comment: 500 on the local disk, a hidden presigned URL in the page source on S3 | public pages with documents | D7 (applied, `e4497d6`) | golden master (12 URLs currently 500 in dev) |
| R22 | S3 disk `url(null)` (folders, `path` NULL): L9+ `Illuminate\Filesystem\AwsS3V3Adapter::url()` throws a `TypeError`; only visible on S3 (the local disk raises a deprecation) | admin file manager / file picker (`/api/files`), every serialized folder | `App\Filesystem\AwsS3V3Adapter` (subclass, `url($path ?? '')`) registered in `AppServiceProvider` (step 1e). **The subclass depends on the framework class: check its constructor, `url()` and `FilesystemManager::createS3Driver()` in every later step (L10–L13)**. Step 2: `Illuminate\Filesystem\AwsS3V3Adapter` byte-identical 9.52.22 → 10.50.3, `createS3Driver()` unchanged in what the subclass uses → no change | `Api/FilesS3UrlTest` + O1 (MinIO) |

---

## 10. What cannot be done without touching frontend, modules (beyond technical changes) or the database

- **Frontend**: nothing. The API contract is kept server-side.
- **Modules beyond technical changes**: nothing. The only module edits are the 5 `$dates` → `$casts` lines (§4).
- **Database**: nothing.
- **Things that do change and need your explicit OK** (not frontend/module/DB, but worth stating):
  - D1: the News feed (existing 500 bug, already planned for removal) cannot stay on `laravelium/feed` past Laravel 8 without a pinned copy.
  - D4: newly generated thumbnail pixels (Croppa engine).
  - S3 (production, §2.3): Croppa 8 redirects rendered thumbnails to the S3 object URL instead of `/<cropPath>` on the app host (R20); Flysystem 3 swallows S3 write errors unless `'throw' => true` (R14, recommendation: set it).
  - D7: dev-only fix for the document pages (production output unchanged with option a).
  - Removing the unused packages `typicms/events`, `typicms/objects`, `eluceo/ical` (done in step 1, D-S1-2).
  - Infrastructure files: `docker/php/Dockerfile`, `phpunit.xml`.

---

## 11. Technical changes in the test suite (to be done only when the step needs them)

| File | Step | Change | Why it is purely technical |
|---|---|---|---|
| `tests/Feature/Images/FileUploadTest.php:43` | 1 (done, 1c-6) | `getSrcDisk()->getAdapter()->getPathPrefix()` → `getSrcDisk()->path('')` | Flysystem 1 API removed; Croppa 8 returns a Laravel `FilesystemAdapter`. Same assertion (root path contains `storage/framework/testing/disks/public`) |
| `tests/Feature/Api/ValidationMessageTest.php` (new) | 1 (1t) | **new test** pinning the 422 message `The given data was invalid.` | added on L8 before the upgrade (D-S1-3); existing tests untouched |
| `tests/Feature/Api/AppendsTest.php` (new) | 1 (1c-7a) | **new test** pinning `append=` on the admin API (`thumb`, `department`, `formatted_year`) | passes on L8 / QB 3, fails on L9 without the QB patch; existing tests untouched |
| `tests/Feature/Api/FilesS3UrlTest.php` (new) | 1 (1e-1) | **new test**: `/api/files` for a folder with sub-folders on an `s3` disk (dummy config, no network) returns 200 and the folder `url` is `AWS_URL/` | passes on L8.83, fails on step 1 before 1e-2 (500, `TypeError`); existing tests untouched |
| `tests/Feature/Models/DateCastsTest.php` (new) | 2 (2t) | **new test** (D-S2-5): for the 5 models with date columns, Carbon on read, stored format, JSON and dirty check | green on L9 with `$dates`, fails on L10 without the casts; existing tests untouched |
| `tests/Feature/Images/FileUploadTest.php:37` | 1 | keep the list of reset singletons; if `'filesystem.default.driver'` stops being resolvable through Croppa, it stays registered by core anyway | no change expected |
| `tests/TestCase.php::apiHeaders()` | 3 (optional) | the comment says `forgetGuards()` didn't exist in 8.30; the current reset still works in L13 | **no change** |
| `phpunit.xml` | 3 | PHPUnit 10+ schema migration (`<coverage>` → `<source>`); all `<server>` values identical | PHPUnit 9 schema is invalid in 11 |
| `tests/Feature/PublicRoutesTest.php`, `tests/Feature/Boot/ApplicationBootTest.php`, `tests/README.md` | 1 (only with D1-a) | delete the two feed tests, drop `'en::news-feed'` and `'feed'` from the boot test lists, remove known issue #1 | this is the Stage-2 feed removal you already planned, not an upgrade workaround |
| `tests/Fixtures/BaselineSeeder.php` | — | **no change** | deprecation only |

---

## 12. Decisions needed before implementation

| ID | Question | Recommendation |
|---|---|---|
| **D1** | News RSS feed: (a) do the already-planned Stage-2 feed removal (the `feed.xml` route, `PublicController::feed()`, `laravelium/feed`, the feed tests) **before step 1**, as a separate commit outside the upgrade steps; or (b) keep it exactly as now (including the known 500) with a pinned copy of `laravelium/feed` 8.0.1 | **(a)**, since it's already decided and avoids carrying an abandoned package. Note the feed also writes a `<link rel="alternate">` tag into every public page `<head>` through `core::public._feed-links` (`TypiCMS::feeds()` lists a feed only while the `news-feed` route exists). With (a) that tag disappears and `app('feed')` is no longer called; with (b) it stays |
| **D2** | Remove `typicms/events`, `typicms/objects`, `eluceo/ical`? | step 0: **kept**. Step 1 (D-S1-2): **removed because they are unused** (commit 1a2). Correction: `eluceo/ical` 0.16.1 allows PHP ≥ 7.1, so it did not block PHP 8.1 |
| **D3** | Pin laravel/ui 4 micro-differences (R9) with overrides in the users copy? | **no** (no visible difference with current forms). Yes if you want byte-exact auth semantics |
| **D4** | Croppa: accept Intervention 3 encoding for **new** thumbnails, or invest in a pinned PHPThumb-based Croppa 5.1 copy (PHP 8.4 compatibility of PHPThumb unverified) | **accept** (existing crops unchanged; URLs/tokens identical) |
| **D5** | Capture golden-master outputs (read-only GETs against dev DB) before step 0 for diffing? | **yes** (covers areas without tests) |
| **D6** | Apply the optional, behavior-neutral `ExplicitNullableParamTypeRector` (2 lines in `Pages/Observers/UriObserver.php` + vendor copies) to silence 8.4 deprecations? | **no for `Modules/`** (policy), **yes for the pinned vendor copies** |
| **D7** | Dev: document pages return 500 because `temporaryUrl()` (inside an HTML comment) is not supported by the local disk | **Decided (a), applied** in commit `e4497d6`, outside the upgrade steps: fallback to `url()` only for disks with the `local` driver. Production (S3) output unchanged. Dev: the 12 golden-master URLs go from 500 to 200 |
| **D8** | How to test S3 locally? MinIO in Docker (own bucket, own credentials, separate DB copy) + optionally a separate non-production AWS test bucket | **Postponed** (2026-10-06): no MinIO and no DB copy for now. Tracked as O1 in "OPEN BEFORE DEPLOYING TO PRODUCTION". Never the production bucket or credentials |
| **D9** | `'throw' => true` on the `s3` disk? | **Yes** (decided 2026-10-06), applied in step 1 |
| **D10** | Branch for step 1 | **`upgrade/step-1` created from `development`** (step 0 was already merged there) |
| **D-S1-1…9** | Step 1 decisions (Croppa crops disk option c, D2 removal, 422 pinning test, Docker `php:8.1-apache-bookworm`, aws-sdk 3.337.3 pin, `throw` only on s3, review commits, no optional bumps, production prerequisites) | see `STEP1_PLAN.md` §6 and §14 below |
| **D-S2-1…9** | Step 2 decisions (genealabs 0.13.9, eloquent-sortable and typidesign unchanged, Laravel 10.50.3, `DateCastsTest`, Symfony stays 6.4, `php:8.2-apache-bookworm`, casts on L9 before the framework, no optional skeleton modernisation) | see `STEP2_PLAN.md` §6 and §15 below |
| **D-S1 QB / per_page** | Query Builder appends (found in 1c) / `per_page` type | **pinned QB 5.7.0 copy with the QB 3 append behaviour** (carry into 6.x in step 2) / **accept `per_page` as integer** |

---

## 13. Effort estimate

| Step | Estimate |
|---|---|
| 0 – pinned copies, L8.83, cleanup, green suite | 0.5–1 day |
| 1 – Laravel 9 / PHP 8.1 (largest: Flysystem 3, Croppa, Permission 6, mailer, proxies/CORS) | 2–3 days |
| 2 – Laravel 10 / PHP 8.2 | 1–1.5 days |
| 3 – Laravel 11 / PHP 8.4 (Carbon compat, PHPUnit 11) | 1–1.5 days |
| 4 – Laravel 12 | 0.5–1 day |
| 5 – Laravel 13 | 0.5–1 day |
| Manual verification + golden-master diffs (§8) | 1–2 days |
| **Total** | **≈ 7–11 working days** |

Size of the change set (excluding `composer.lock` and the verbatim `packages/` copies):
- `app/`: 3 files
- `config/`: 3 files
- `Modules/`: 5 files, 5 lines
- pinned-copy patches: about 4 files
- tests: 1 line + `phpunit.xml`
- Docker: 1 file


---

## 14. Step 1 — Laravel 9.52 / PHP 8.1 (done 2026-10-21, branch `upgrade/step-1`, pushed, not merged; S3 fix 1e 2026-10-22)

| Commit | Content |
|---|---|
| `53c533b` 1t | new `ValidationMessageTest` (422 message), on L8 |
| `45e8eba` 1a | `fzaninotto/faker` → `fakerphp/faker` |
| `f43a94b` 1a2 | removed unused `typicms/events`, `typicms/objects`, `eluceo/ical` (and their pinned copies) |
| `0e3c2f4` 1b | PHP 8.1 on L8.83 (`php:8.1-apache-bookworm`), golden master identical |
| `b395cad` | `tools/golden-master/cors-headers.sh` (CORS reference) |
| `9e0ee0e` 1c-1 | pinned copies: `typicms/roles` Role (Permission 6 signatures), `typicms/sitemap` → ultrono |
| `81618ad` 1c-2 | composer: Laravel 9.52.22 and the step 1 package set; `aws/aws-sdk-php` pinned 3.337.3 |
| `d13723d` 1c-3 | `app/`: TrustProxies, Kernel CORS, `Handler::invalidJson()` |
| `ca4762a` 1c-4 | `config/`: trustedproxy, filesystems (`$s3`, `throw`, `croppa_crops`), croppa (Croppa 8, option c) |
| `dc8d83c` 1c-5 | `typicms/users` User: Permission 3 default guard |
| `c623903` 1c-6 | technical test line `FileUploadTest.php:43` |
| `8b1c6a9` 1c-7a | new `AppendsTest` (append=) |
| `96072ac` 1c-7b | pinned `spatie/laravel-query-builder` 5.7.0 with the QB 3 append behaviour |

Results (Docker, Laravel 9.52.22, PHP 8.1.34): full suite **67 tests, 497 assertions, OK** (65 before + 2 new). Golden master `before-step-1` (L8.83, PHP 7.4) vs `after-step-1c`: 409 of 429 identical (all public pages, sitemap, search, auth pages); the 20 paginated admin API lists differ only by the `per_page` type (accepted), `/api/history` also by two history rows written to the dev DB in between (not by the upgrade). CORS headers of `/api` identical. No file in `Modules/`, `resources/`, `routes/`, `public/` or `database/` changed.

Findings during step 1 (all handled): Permission 6 default guard (→ `typicms/users` patch), Query Builder 5 removed `append=` (→ pinned QB copy), stale Permission 3 cache breaks v6 (→ O6), `per_page` integer (accepted), `voku/portable-ascii` 2.1.1 (Str::slug unchanged on 9,793 real strings), `egulias/email-validator` 4.0.4 (→ O8), `config/croppa.php` must read `env()` (config load order). Details: `STEP1_PLAN.md`.

**Local S3 test on MinIO (O1, 2026-10-22)** — `tools/s3-test/` (`docker-compose.s3.yml`, `.env.s3test.example`), report `tools/s3-test/S3_TEST_REPORT.md`. Own MinIO bucket and dummy credentials, separate DB copy `akademija_s3test` from the production dump; no production bucket, credentials or database. Same checklist on `development` (L8.83 / PHP 7.4, reference) and on `upgrade/step-1`:

| Commit | Content |
|---|---|
| `33f2935` 1e-1 | new `Api/FilesS3UrlTest`: `/api/files` with a folder that has sub-folders on an `s3` disk (dummy config, no network) → 200, folder `url` = `AWS_URL/`. Passes on L8.83, fails on step 1 (500) |
| `f2ab694` 1e-2 | `AppServiceProvider` registers the `s3` driver as `App\Filesystem\AwsS3V3Adapter` (subclass of `Illuminate\Filesystem\AwsS3V3Adapter`, `url($path ?? '')`); `Modules/` unchanged |
| `25cb479` | S3 test tooling + report (`docker-compose.s3.yml`, `.env.s3test.example`, `tools/s3-test/`) |

**Finding (S3 only, missed by the local suite and the golden master):** folders are `files` rows with `path` NULL and `File::$appends` contains `url`. `Storage::url(null)` on S3 returned `AWS_URL/` on Laravel 8 (Flysystem 1); on Laravel 9 `Illuminate\Filesystem\AwsS3V3Adapter::url()` passes the null to `PathPrefixer::prefixPath(string)` → `TypeError`, which `File::getUrlAttribute()` does not catch (`catch (Exception)`), so `/api/files` (admin file manager and the file picker of every module) returned 500 for every folder with sub-folders, including the root folder. On the local disk the same call only raises a PHP 8.1 deprecation (logged), so nothing failed locally. Fixed by 1e-2 without touching `Modules/`; after the fix the step 1 MinIO run matches the reference in every check. **The subclass must be checked in every later step (L10–L13)** against the framework class and `createS3Driver()` (R22, §8). Full suite after 1e: **68 tests, 501 assertions, OK**.
## 15. Step 2 — Laravel 10.50.3 / PHP 8.2 (done 2026-10-22, branch `upgrade/step-2` from `development` `7a60a92`, not pushed, not merged; MinIO run and documentation 2026-10-29)

Analysis and decisions: `STEP2_PLAN.md` (D-S2-1…9, all as recommended).

| Commit | Content |
|---|---|
| `455da38` 2t | new `Models/DateCastsTest` (D-S2-5), on L9 with `$dates`; fails for each model when its `$dates` is removed |
| `60ef022` 2a | PHP 8.2 on L9.52 (`php:8.2-apache-bookworm`, PHP 8.2.35, D-S2-7); golden master identical |
| `ac2b8cd` 2b | the 5 module models `$dates` → `'datetime'` casts, still on L9 (§4); read-only check on all dev DB rows: 0 differences; golden master identical |
| `9d05bde` 2c | pinned `spatie/laravel-query-builder` 6.4.4, verbatim copy (reference `ab9c4c3`, sha256-checked) |
| `56e65ed` 2d | QB 6.4.4: the append patch re-applied by hand (§3.4), `PATCHES.md` |
| `3ba6974` 2e | `typicms/translations` `MixedLoader::loadPaths` (§3.4), `PATCHES.md` |
| `06f99cb` 2f | composer: explicit package list, no `-W` (11 updates, 2 installs, 0 removals): laravel/framework 10.50.3, laravel/ui 4.6.3, genealabs 0.13.9 (+ pivot-events 10.0.1), QB 6.4.4, honeypot 1.5.0, collision 7.12.0, laravel-ignition 2.9.1; transitive Monolog 3.12.1, `laravel/prompts` 0.1.25, `carbon-doctrine-types` 2.1.1; `php` ^8.2; Symfony stays 6.4 LTS; eloquent-sortable 4.5.2 and typidesign 1.0.4 kept |

2c–2e are only green together with 2f (QB 6 and `loadPaths()` need L10), as planned.

Results (Docker, Laravel 10.50.3, PHP 8.2.35): full suite **73 tests, 552 assertions, OK** (68 before + 5 in `DateCastsTest`); `DateCastsTest` fails on L10 without 2b (checked, not committed). Golden master `before-step-2` (L9.52, PHP 8.1) vs `after-step-2`: **429 of 429 identical** (414 × 200, 14 × 404, 1 × 500 `/api/news/1019/files`, the same 500 as in every capture), incl. all 26 `/api` URLs (`append=`, `include=`, `fields`, `filter`, `sort`) and the Users list with `fields[roles]` + `include=roles` (qualified columns in QB 6, same JSON). CORS headers of `/api` identical. **MinIO run `step-2`** identical to `step-1` in every check, S3 trace identical (O1, report). In `Modules/` only the 5 cast lines changed; nothing in `resources/`, `routes/`, `public/`, `database/`, `app/` or `config/`.

Verified without a code change: `App\Filesystem\AwsS3V3Adapter` (parent byte-identical, R22), `typicms/users` and `typicms/roles` patches (Permission stays 6.25), laravel/ui 4 auth traits (byte-identical to 3.4.6), `Handler::invalidJson()` (parent unchanged), `$routeMiddleware` (still merged by L10), `DB::raw()` uses (all in `select()`), Monolog 3 (standard config only), PHP 8.2 lint of 629 files and class-load of 395 project / pinned-copy classes under L10 on the trial install (STEP2_PLAN §3.1, §3.2).

Re-run on the committed step 2 state (2026-10-29, PHP 8.2.35 + Laravel 10.50.3):
- **Class load** (one PHP process per class, autoloader only, every error and deprecation reported): **391 classes** in `app/`, `Modules/` and `packages/*/*/src`, **390 load without error or deprecation**. The only failure is `Settings\Models\Setting` (`Class "Eloquent" not found`: it extends the `Eloquent` alias, which only exists in a booted app, same as on L9); in a booted app it loads with 0 errors/deprecations. 391 is also the number of class/interface/trait/enum declarations found by a direct grep of those directories; STEP2_PLAN counted 395 on the trial install. The difference of 4 cannot be traced, because that harness was not kept; the re-run covers every class declared in the committed code.
- **Test first** (as in 1c-7a): with the 2b lines reverted in the working tree (all 5 models back on `$dates`), `DateCastsTest` on L10 gives `FAILURES! Tests: 5, Assertions: 10, Failures: 5`: every model fails with `Failed asserting that '<date>' is an instance of class "Carbon\Carbon"` (dates come back as strings). Restored with `git checkout`, not committed; afterwards `OK (5 tests, 51 assertions)`.

Findings during step 2:
- Plan corrections (§1.1, R9): genealabs L10 line is 0.13.x/11.x/12.x, 0.13.9 chosen; eloquent-sortable and typidesign need no bump; laravel/ui ^4.6; the ui "micro-differences" of R9 already came with 3.4.6 (step 0b).
- Laravel 10 `csrf_field()` adds `autocomplete="off"` to the hidden `_token` input. Only used by the logout form in the logged-in navbar (`resources/views/vendor/core/_navbar.blade.php:39`); found through the MinIO run's 404 page size (+19 bytes), not covered by the golden master (anonymous pages). No visible or functional change.
- Deploy: `cache:clear` is mandatory (genealabs 0.13.9 cache keys, O6); production PHP 8.2 (O5).
- The dev DB `akademija` has 5 migrations shown as *Pending* (announcements, classschedules tables) although the tables exist: the production dump never recorded them in `typicms_migrations`. Unrelated to the upgrade; `migrate` is never run (§5).
