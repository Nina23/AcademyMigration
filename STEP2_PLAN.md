# STEP 2 PLAN — Laravel 9.52 → 10.x, PHP 8.1 → 8.2

**Analysis only (2026-10-22). No code, config, test or database file was changed.**
Branch `upgrade/step-2` created from `development` (`7a60a92`, = `origin/development`, Step 1 merged). Not committed, not pushed.

Everything below was checked against the real code: the installed vendor (Laravel 9.52.22), downloaded sources of the targets (laravel/framework 10.48.29 and 10.50.3, spatie/laravel-query-builder 5.7.0 and 6.4.4, genealabs 0.13.9 / 11.0.1 / 12.0.4, laravel/ui 4.6.3), Packagist metadata, a **trial dependency resolution and install in a throwaway copy** (container `/tmp`, not the repo), a **PHP 8.2 lint** of all code, and a **PHP 8.2 class-load of every project / pinned-copy class against Laravel 10**.

## 1. Baseline (current state: Laravel 9.52.22, PHP 8.1.34)

| Check | Result |
|---|---|
| Full suite (Docker) | **68 tests, 501 assertions, OK** |
| Golden master `before-step-2` (429 URLs) | 414 × 200, 14 × 404, 1 × 500 (`/api/news/1019/files`, the same 500 as in every Step 1 capture) |
| `before-step-2` vs `after-step-1c` | 427 identical; 2 differ **by data only**: `/api/files` lists 2 new files (ids 9375/9376) and `/api/history` their history rows, uploaded to the dev DB `akademija` on 2026-10-22 10:51 and 11:01 (before the MinIO runs; not by the test tooling, which only uses `akademija_s3test`). Reference for Step 2 is `before-step-2` |
| CORS reference `cors-before-step-2.txt` | identical to `cors-after-step-1c.txt` |

## 2. Summary

- **Required:** Dockerfile PHP 8.2; composer (Laravel 10 + the packages that block it); the 5 `$dates` → `$casts` lines (the only module edits, UPGRADE_PLAN §4); `MixedLoader::loadPaths`; Query Builder 6.4.4 pinned copy with the Step 1 append patch.
- **Not required (verified):** `App\Filesystem\AwsS3V3Adapter` (parent class byte-identical), `typicms/users` and `typicms/roles` patches (Permission stays 6.25), any change in `app/`, `config/`, `routes/`, Blade, tests.
- **Public site / module behaviour:** no change expected anywhere. The 5 casts are proven identical on all real rows (§3.3). No item touches public views, layouts, assets, routes or URLs.
- **Decisions for you** (§6): genealabs line, eloquent-sortable bump, typidesign bump, Laravel 10.50.3 vs 10.48.29, the new date test (D-S2-5), commit order.

## 3. Items

### 3.1 Dockerfile → PHP 8.2

| | |
|---|---|
| Now | `FROM php:8.1-apache-bookworm` |
| Change | `FROM php:8.2-apache-bookworm` (comment: D-S1-4 / D-S2-7). Extension list unchanged |
| Verified | image pulled: **PHP 8.2.35, Debian 12.15 (bookworm)**. Laravel 9.52 supports PHP 8.2, so this can go first on L9 (as 1b did for 8.1) |
| PHP 8.2 changes checked | **lint of 629 files** (`app/`, `Modules/`, `config/`, `routes/`, `database/`, `tests/`, `bootstrap/`, all `packages/`): 0 errors, 0 compile-time deprecations. No `${var}` interpolation, `utf8_encode/decode`, `HTML-ENTITIES`, partially-supported callables. **Locale-insensitive `strtolower`/`ucfirst`/… (8.2):** no effect — core `SetLocale` builds `sr_SR`, `en_EN`, `sr-latn_SR-LATN`, which are not valid locale names, so `setlocale()` already fails today and PHP stays in the `C` locale (Docker has no `sr` locales either). **Dynamic properties** are a runtime deprecation only (logged to the `null` channel; tests ignore deprecations), fatal only in PHP 9 |
| Public / module impact | none |
| Risk | low |
| Confirmed by | full suite + golden master on L9.52 + PHP 8.2 (commit 2a) |

### 3.2 composer.json (L10 column of UPGRADE_PLAN §1)

Trial resolution in a throwaway copy (`composer update` with an explicit package list, **no `-W`**; with `-W` Composer also lifts `symfony/string`, `css-selector`, `event-dispatcher` to 7.4 and `spatie/laravel-package-tools`, which nothing needs):

| Package | Now (locked) | Target | Why / note |
|---|---|---|---|
| php | ^8.1 | **^8.2** | QB 6.4 requires ^8.2 |
| laravel/framework | ^9.52 (9.52.22) | **^10.48** → locks **v10.50.3** | 10.49–10.50 are post-EOL security releases (diffed against 10.48.29: only `unserialize()` gadget guards in the Redis cache store and session store, Forge/Vapor host handling in `TrustProxies`, version constant, two new helper files). None applies here (file sessions, no Redis, own host). D-S2-4 |
| laravel/ui | ^3.0 (3.4.6) | **^4.6** (4.6.3) | 3.4.6 allows only ^8.42\|^9. **The auth traits used by `typicms/users` (`AuthenticatesUsers`, `ResetsPasswords`, `SendsPasswordResetEmails`, `RegistersUsers`, `VerifiesEmails`, `ThrottlesLogins`, `RedirectsUsers`) are byte-identical in 3.4.6 and 4.6.3**; `UiServiceProvider` identical; only scaffolding commands/stubs differ. 4.6.3 allows L9–L13 (no further bump) |
| genealabs/laravel-model-caching | ^0.12.5 | **0.13.9** (recommended) or ^12.0 (plan) | 0.12.5 is L9 only. See §3.8, D-S2-1 |
| genealabs/laravel-pivot-events | 9.0.4 (transitive) | 10.0.1 | pulled by genealabs 0.13.9 |
| spatie/laravel-query-builder | 5.7.0 (pinned copy) | **6.4.4 (pinned copy + append patch)** | §3.5 |
| typidesign/laravel-artisan-translations | ~1.0.3 (1.0.4) | **unchanged** (recommended) | 1.0.4 requires only `php ^7.0\|^8.0` (no `illuminate/*` constraint), so Composer accepts it on L10; its code is two plain `Illuminate\Console\Command` subclasses (`handle()`) + a `ServiceProvider`, nothing L10 removed. 2.0 is L9 only, 3.2 allows L10–L12, 4.0 L10–L13. Artisan commands only. Plan said ^3.0. D-S2-3 |
| msurguy/honeypot | ~1.1.5 (1.1.7) | **^1.5** (1.5.0) | 1.1.7 allows only up to `9.*`. Not used in code/views |
| spatie/eloquent-sortable | ^4.5 (4.5.2) | **unchanged** (recommended) | 4.5.2 already allows `^9.31\|^10\|^11\|^12`. Not used by any code. Plan said ^5.0 (needs PHP 8.2); not required until L13. D-S2-2 |
| laravel/tinker | ^2.0 (2.11.1) | unchanged | 2.11.1 allows L6–L12 |
| barryvdh/laravel-debugbar (dev) | ^3.5 (3.16.0) | unchanged | 3.16.0 allows ^9–^12 |
| nunomaduro/collision (dev) | ^6.4 | **^7.0** (7.12.0) | L10 line; dev only |
| spatie/laravel-ignition (dev) | ^1.7 | **^2.0** (2.9.1; 2.10+ needs L11) | dev error page only; + `spatie/ignition` 1.16.0, new `spatie/error-solutions` 1.1.3. `config/ignition.php`, `config/flare.php` kept (merged defaults) |
| phpunit/phpunit (dev) | ^9.3 (9.6.38) | unchanged | L10 conflicts only with `>=11`; PHPUnit 10 is optional (plan: step 3) |
| mockery/mockery (dev) | 1.6.15 | unchanged | L10.48.1 conflict `1.6.8` only |
| Transitive, required by Laravel 10 | | `monolog/monolog` 2.11.1 → **3.12.1**, new `laravel/prompts` 0.1.25, `carbonphp/carbon-doctrine-types` 3.2.1 → **2.1.1** (L10 conflicts with ≥3) | Monolog 3: `config/logging.php` uses only standard drivers/handlers (`single`, `daily`, `stderr`/`StreamHandler`, `SyslogUdpHandler`, `NullHandler`, level names) → no change. No code uses Monolog classes |
| Unchanged and verified for L10 | | `bkwld/croppa` 8.0.1, `spatie/laravel-permission` 6.25.0, `maatwebsite/excel` 3.1.70, `ultrono/laravel-sitemap` 9.5.0, `laracasts/presenter` 0.2.9, `laracasts/utilities` 3.2.5, `league/flysystem-aws-s3-v3` 3.32.0 + `aws/aws-sdk-php` 3.337.3 (pin kept, O1), `symfony/mailgun-mailer` + `http-client` 6.4, Sidebar VCS fork, all `typicms/*` / `spatie/laravel-translatable` / `nestablecollection` copies (constraints already ^10–^13) | |

Trial lock result (typidesign kept at 1.0.4): **11 updates, 2 installs, 0 removals**, Symfony stays 6.4 LTS. Command (for 2f):
`composer update laravel/framework laravel/ui genealabs/laravel-model-caching genealabs/laravel-pivot-events spatie/laravel-query-builder msurguy/honeypot nunomaduro/collision spatie/laravel-ignition spatie/flare-client-php spatie/ignition monolog/monolog laravel/prompts carbonphp/carbon-doctrine-types`
(with typidesign ^3.0 it is 12 updates; the class-load check below ran with that variant, 3.2 — typidesign is vendor code and not part of the 395 classes).
Not installed: `doctrine/dbal` (only in genealabs' require-dev).

**Class-load check:** with the trial install, every class of `app/`, `Modules/` and the pinned copies (395) was loaded under **PHP 8.2.35 + Laravel 10.50.3**, one process per class: **394 load without error or deprecation**; the one failure (`Settings\Models\Setting` extends the `Eloquent` alias, which only exists in a booted app) is a harness artefact and the same on L9. So no pinned copy or module overrides a framework method incompatibly.

Public / module impact: none. Risk: medium (framework major), covered by the full suite + golden master + MinIO run.

### 3.3 The 5 models: `$dates` → `$casts` (the only module edits, UPGRADE_PLAN §4)

Laravel 10 removed the `$dates` property: `getDates()` returns only `created_at`/`updated_at` (verified in 10.48.29 `HasAttributes::getDates()`). Without the change these columns become plain strings and `->format()` in presenters/views fatals.

| File | Line now | After |
|---|---|---|
| `Modules/Advertismentboards/Models/Advertismentboard.php:31` | `protected $dates = ['date'];` | `protected $casts = ['date' => 'datetime'];` |
| `Modules/Announcements/Models/Announcement.php:36` | `protected $dates = ['date', 'expiry'];` | `protected $casts = ['date' => 'datetime', 'expiry' => 'datetime'];` |
| `Modules/Classschedules/Models/Classschedule.php:43` | `protected $dates = ['from_date', 'to_date', 'expiry'];` | `protected $casts = ['from_date' => 'datetime', 'to_date' => 'datetime', 'expiry' => 'datetime'];` |
| `Modules/Classschedules/Models/ClassScheduleItems.php:34` | `protected $dates = ['from_date', 'to_date'];` | `protected $casts = ['from_date' => 'datetime', 'to_date' => 'datetime'];` |
| `Modules/News/Models/News.php:25` | `protected $dates = ['date'];` | `protected $casts = ['date' => 'datetime'];` |

None of the 5 models has a `$casts` property today (no merge needed); Spatie Translatable 4.6 adds its `array` casts in `getCasts()` on top. Columns: `datetime` (Advertismentboards, Announcements, Classschedules, ClassScheduleItems) and `date` (News).

**Same value, same DB write, same JSON — verified, not only read:** read-only experiment on L9.52 against the dev DB (nothing saved): for every row of the 5 tables, the model with `$dates` vs. the same model with the `'datetime'` casts:

| Model | Rows | Non-null date values | Value diffs (class + `Y-m-d H:i:s.u e`) | `toArray()`/JSON diffs | Write diffs (8 input forms × columns) / dirty check |
|---|---|---|---|---|---|
| Advertismentboard | 0 | 0 | 0 | 0 | 0 |
| Announcement | 477 | 954 | 0 | 0 | 0 |
| Classschedule | 3 | 0 (all null) | 0 | 0 | 0 |
| ClassScheduleItems | 7 | 14 | 0 | 0 | 0 |
| News | 1004 | 1004 | 0 | 0 | 0 |

Write inputs: `'2026-10-05'`, `'2026-10-05 10:00'`, `'…10:00:07'`, ISO 8601 with offset, `null`, `''`, `Carbon`, `DateTime`; raw attribute after assignment identical (`fromDateTime()` with the same `Y-m-d H:i:s` format). Code path: both use `isDateAttribute()` (`$dates` or `isDateCastable()`), `asDateTime()`, `fromDateTime()`, `serializeDate()`; `originalIsEquivalent()` takes the same date branch; these methods are identical in 10.50.3 (diffed; `asDateTime` only drops an unused catch variable, `setAttribute` only adds the unused `hashed` cast).

Public / module impact: **none** (same Carbon, same stored format, same JSON). Risk: low. Confirmed by: `Models/NewsModelTest`, `Models/ClassscheduleTest`, `PublicRoutesTest`, golden master (news/announcements/classschedules pages and API), **new `Models/DateCastsTest`** (D-S2-5, see 2t), and re-running the experiment on L10 after the change.

### 3.4 `typicms/translations` MixedLoader: `loadPath` → `loadPaths`

| | |
|---|---|
| Now | `packages/typicms/translations/src/Loaders/MixedLoader.php:29`: `return $this->loadPath($this->path, $locale, $group);` |
| Change | `return $this->loadPaths($this->paths, $locale, $group);` + `PATCHES.md` entry |
| Why | L10 `FileLoader` replaced `$path`/`loadPath()` with `$paths`/`loadPaths()` (verified in 10.48.29). Constructor `array\|string $path` → `$this->paths = [$path]`; `loadPaths([$path])` = `array_replace_recursive([], require file)` = the same array as `loadPath()` |
| Not changed | the provider still constructs `new MixedLoader($app['files'], $app['path.lang'], $repository)`: one path (`resources/lang`; L10 `langPath()` still prefers `resources/lang` when it exists). Laravel 10's own loader would add the framework `lang/` directory; TypiCMS replaces that loader, so **framework language files stay unloaded, as today**. `loadNamespaced()`, `loadJsonPaths()` and `loadFromDatabase()` (DB::raw in `select()`, still valid) untouched |
| Public / module impact | none (same translations) |
| Risk | low |
| Confirmed by | `Translations/*` (13 tests), `Auth/AuthenticationTest` (sr DB translations), `Admin/NewsAdminCrudTest` (validation messages from `resources/lang`), golden master |

Note: `loadPaths` does not exist on L9, so this commit is only green together with the composer commit.

### 3.5 spatie/laravel-query-builder 5.7.0 → 6.4.4 (pinned copy + append patch)

**Copy:** replace `packages/spatie/laravel-query-builder` with a byte copy of the 6.4.4 dist (reference to be recorded from the lock), `"version": "6.4.4"` in its `composer.json`, then re-apply the Step 1 patch and update `PATCHES.md` (5.7.0 → 6.4.4).

**Carrying the append patch (Step 1, 1c-7b):** the patch is three pieces: `src/Concerns/AppendsAttributesToResults.php` (verbatim QB 3.6.3 trait), `QueryBuilder.php` (`use` lines + `use AppendsAttributesToResults;` + the Model/Collection/paginator hooks in `__call()`), `composer.json` version. Dry-run on 6.4.4: all three hunks apply (imports with fuzz 1, `__call` offset −21) → applied **by hand**, not with fuzz. 6.4.4 `__call()` body is unchanged from 5.7.0; `QueryBuilderRequest::appends()`, the `append` parameter and `InvalidAppendQuery::appendsNotAllowed()` still exist; the trait's untyped `$allowedAppends` does not clash with 6.x typed properties; our code never calls `allowedAppends()`, so every requested append is applied as in QB 3.

**What else changes 5.7 → 6.4.4 (full `src/` diff reviewed):**

| Area | Change | Effect here |
|---|---|---|
| Typing | typed properties / signatures; constructor `EloquentBuilder\|Relation $subject` (promoted), `InvalidSubject` removed | all 32 `QueryBuilder::for()` calls pass a model class or a relation. `allowedFields`/`allowedIncludes` are `?Collection = null`; `allowedSorts`/`allowedFilters` are only read after being set |
| `Filter` interface | `__invoke(Builder $query, mixed $value, string $property)` | core `FilterOr::__invoke(Builder $query, $value, string $property): Builder` stays compatible (class-load check passed) |
| Fields | `getRequestedFieldsForRelatedTable($relation, ?$tableName)`; new config `convert_relation_table_name_strategy` and `convert_field_names_to_snake_case` (default `false`) | `allowedFields()`/`ensureAllFieldsExist()`/`prependField()` identical. Our `config/query-builder.php` is the old QB 3 file; QB 6 merges its defaults (shallow `mergeConfigFrom`) and every call passes the same default inline → both new options off |
| Includes with relation fields | `IncludedRelationship` now selects `qualifyColumns($fields)` | **only the Users admin list sends this** (`fields[roles]=name&include=roles`): SQL `select roles.name` instead of `select name` on the BelongsToMany; the pivot columns are added the same way → same JSON expected. Covered by golden master URL 441 (`/api/users?…&fields[roles]=name&include=roles`). History's dotted fields are its own table (`fields[history]`) |
| Filters / sorts | `allowedFilters` flattens one level; partial filter `array_filter` callback; `ESCAPE` only for sqlite/sqlsrv; default-filter closure loses a trailing `return;` | no effect for our flat filter lists / MariaDB |

Public / module impact: none (admin API only; JSON pinned). Risk: medium (the patch). Confirmed by: `Api/AppendsTest` (must stay green), `Api/*`, golden master (all 26 `/api` URLs, incl. `append=`, `include=`, `fields`, `filter`, `sort`), CORS reference.

### 3.6 `App\Filesystem\AwsS3V3Adapter` (S3 `url(null)` fix, R22) on Laravel 10

Diffed 9.52.22 → 10.48.29 → 10.50.3: `Illuminate\Filesystem\AwsS3V3Adapter` is **byte-identical**; `FilesystemManager::createS3Driver()`, `callCustomCreator()`, `resolve()` and `FilesystemAdapter::__construct()`, `getDriver()`, `getAdapter()`, `getConfig()`, `concatPathToUrl()`, `temporaryUrl()` identical. Only `formatS3Config()` (session `token` handled separately; we set none) and `createFlysystem()` (`retain_visibility` for Cloudflare R2 endpoints only) changed — no effect. **No change needed.** Confirmed by `Api/FilesS3UrlTest` + the MinIO run on step 2 (`tools/s3-test/run.sh step-2`, compare with `old-code` / `step-1`). Public / module impact: none.

### 3.7 `typicms/users` default-guard patch and `typicms/roles` patch

`spatie/laravel-permission` stays 6.25.0 (allows L8.12–L13), so the Permission contract and `Guard::getNames()` are unchanged and both patches stay as they are. Both classes loaded under L10 + PHP 8.2 (class-load check). **No change.** Confirmed by `Admin/AdminAccessTest`, `Api/NewsApiTest::test_api_enforces_permissions`. Deploy note: Permission cache format unchanged in this step.

### 3.8 genealabs/laravel-model-caching (D-S2-1)

0.12.5 cannot stay (L9 only; L10 database `Expression`s are no longer strings, which 0.12 puts into cache keys). Candidates that allow L10, diffed against 0.12.5:

| Version | Allows | Changes vs 0.12.5 |
|---|---|---|
| **0.13.9** (recommended) | L10 only | only the L10 adaptations: `Expression` → string in cache keys (columns, wheres, orders), `having` clauses and paginate `total` in the key, early return when caching is disabled, pivot-event callbacks with the pivot-events 10 signature. Same config file. Affects cache keys/invalidation only, not results |
| 11.0.1 | L10–L11 | 0.13.9 + the same changes as below in part |
| 12.0.4 (plan) | L10–L12 | 0.13.9 + `strict_types`, **eager-load constraint closures hashed into the cache key**, rewritten subquery-binding handling, DateTime/bool/int values in keys, `diffInSeconds(..., true)`, `hasGlobalMacro`, switch to the renamed `mikebronner/laravel-pivot-events`. These fix stale-cache edge cases (two queries that differ only in an eager-load constraint could share a key) — i.e. **they can change what a cached query returns** in such edge cases |

Recommendation: **0.13.9** in step 2 (smallest behaviour surface); the L11+ line is needed in step 3 anyway, decide 11/12/13 there with the same diff. Packagist marks the package abandoned in favour of `mike-bronner/laravel-model-caching` (same author) — keep in mind for step 3–5 (R11). Deploy: cache keys change → `cache:clear` on deploy (already O6). Public / module impact: none (cache layer only). Confirmed by the whole suite (array store = caching active) + golden master.

### 3.9 Other packages

- **spatie/eloquent-sortable:** not used by any code; 4.5.2 already allows L10 → **no bump** (D-S2-2).
- **laravel/ui 4:** see §3.2 (auth traits identical). **Correction to R9:** the listed micro-differences (`boolean('remember')`, `Password::defaults()`, transliterated throttle key, `auth.password_confirmed_at`) are already in 3.4.6, i.e. they arrived with step 0b, not with ui 4; ui 4 changes nothing at runtime.
- **collision 7, ignition 2, honeypot 1.5** (and typidesign if bumped): dev / artisan / unused — no runtime effect on the site.

### 3.10 Anything else Laravel 10 removes or changes (scanned in `app/`, `Modules/`, `packages/`, `config/`, `routes/`, `tests/`, `database/`, `bootstrap/`)

| L10 change | Found | Effect |
|---|---|---|
| `$dates` removed | 5 models | §3.3 |
| `DB::raw()` Expressions not castable to string | 3 uses (`Tags ApiController`, history `ApiController`, `MixedLoader`), all passed to `select()/addSelect()` | none |
| Monolog 3 | standard config only | none |
| `$routeMiddleware` → `$middlewareAliases` | `app/Http/Kernel.php` | L10 still merges `$routeMiddleware` (verified) → no change |
| `registerPolicies()` | `AuthServiceProvider` | still exists in L10 |
| `Handler::invalidJson()` override | `app/Exceptions/Handler.php` | parent method identical in L10 → 422 message stays pinned |
| FormRequest `after()` method now called automatically | no FormRequest defines `after()` (only a form-builder `Label::after()`) | none |
| `Bus::dispatchNow`, `Redirect::home`, `assertDeleted`, `getBaseQuery`, `MocksApplicationServices`, `RateLimiter::attempt`, `QueryException` constructor, `Validator::extend`, public-path binding, `getDoctrine*` | none | — |
| Paginator JSON (`toArray`, `linkCollection`, `url`) and date serialization (`serializeDate`, `attributesToArray`, `addCastAttributesToArray`) | — | identical 9.52.22 → 10.50.3 |
| Translation loader paths | §3.4 | — |
| `minimum-stability: dev` (L10 guide suggests stable) | composer.json | keep (`prefer-stable: true`; changing it is not required) |

## 4. What in UPGRADE_PLAN.md is wrong or outdated

1. **§1.1 genealabs L10 = ^12.0:** the L10 lines are 0.13.x (L10), 11.x (L10–11), 12.x (L10–12); 12.x carries cache-key behaviour changes (§3.8). Recommendation 0.13.9 (D-S2-1).
2. **§1.1 eloquent-sortable L10 = ^5.0:** not required; 4.5.2 allows up to L12. 5.x needed only for L13.
3. **§1.1 laravel/ui L10 = ^4.2:** use ^4.6 (4.6.3 covers L9–L13). **R9 / §3.4 wording:** the ui micro-differences came with 3.4.6 (step 0b); ui 4 auth traits are identical to 3.4.6.
4. **§1.1 typidesign L10 ^3.0 / L11 ^4.0:** 1.0.4 has no framework constraint and works on L10, so no bump is required in step 2 (D-S2-3); if a bump is wanted later, 3.2 covers L10–L12 and 4.0 L10–L13.
5. **§1.1 laravel/framework L10 ^10.48:** resolves to **10.50.3** (post-EOL security releases); state it.
6. **§1.1 tinker ^2.8 / debugbar:** no change needed (2.11.1 / 3.16.0 already allow L10).
7. **§8 step 2 row** ("genealabs 12; eloquent-sortable 5; laravel/ui 4; typidesign 3"): update per D-S2-1…3 (recommended: genealabs 0.13.9, no sortable/typidesign bump, ui ^4.6, honeypot ^1.5 — honeypot is missing from the row); add Monolog 3, `laravel/prompts`, `carbon-doctrine-types` 2.x (transitive), Symfony stays 6.4.
8. **§4 / §13 "5 modules":** it is 5 models in 4 modules (Classschedules has two).
9. **§3.5 / §7 "QB 6.4 parses them the same way":** true for parameters; one SQL difference (qualified columns for `fields[relation]` + `include`, Users admin list), JSON expected identical (golden master URL 441).
10. **O6** says "after deploying step 1": the cache resets are needed after every step's deploy (step 2: genealabs key format).

## 5. Proposed commit order (small, reviewable; as in step 1 only the marked commits must be green)

| # | Commit | Green? |
|---|---|---|
| **2t** | new `tests/Feature/Models/DateCastsTest.php` (D-S2-5): for each of the 5 models, a date attribute is a `Carbon` instance with the stored value, the raw DB value after save has the `Y-m-d H:i:s` / `Y-m-d` format, and the JSON (`toArray()`) has the `serializeDate()` format. Written and green on L9 with `$dates` | **yes** (L9) |
| **2a** | Dockerfile `php:8.2-apache-bookworm` (still L9.52) | **yes**: suite + golden master identical |
| **2b** | the 5 module models `$dates` → `$casts` (`'datetime'`), still on L9 — the casts work on L9 too, so equivalence is proven on the old framework before the framework changes; re-run the read-only experiment | **yes**: suite (incl. 2t) + golden master identical |
| 2c | `packages/spatie/laravel-query-builder`: byte copy of 6.4.4 + `version` (verbatim, reviewable on its own) | no (needs L10) |
| 2d | QB 6.4.4: re-apply the append patch + `PATCHES.md` | no |
| 2e | `typicms/translations` `MixedLoader::loadPaths` + `PATCHES.md` | no |
| **2f** | composer.json + composer.lock (§3.2 package list, no `-W`) | **yes**: full suite, `AppendsTest`, `FilesS3UrlTest`, golden master `after-step-2` vs `before-step-2`, CORS, casts experiment on L10, PHP 8.2 class-load |
| — | MinIO run `tools/s3-test/run.sh step-2` (no commit; results into the report) | — |
| 2g | documentation: UPGRADE_PLAN.md (§1, §3.4, §8, R9, R11, O3/O6, §15 step 2 record), tests/README.md, `tools/s3-test/S3_TEST_REPORT.md` (step 2 column) | — |

Also to verify in 2t/2f (the test-first check, as in 1c-7a): `DateCastsTest` must **fail** on L10 without 2b (dates as strings) — checked by temporarily running it against the 2f state with the 2b lines reverted in the working tree (not committed).

## 6. Open questions / decisions for you

| ID | Question | Recommendation |
|---|---|---|
| **D-S2-1** | genealabs: 0.13.9 (L10 only, minimal) / 11.0.1 / 12.0.4 (L10–L12, plan, with cache-key behaviour fixes) | **0.13.9**; choose the L11+ line in step 3 with the same diff |
| **D-S2-2** | eloquent-sortable: keep 4.5.2 or bump to ^5.0 | **keep** (unused, not required; policy D-S1-8) |
| **D-S2-3** | typidesign: keep 1.0.4, or ^3.0 (3.2, L10–L12, plan) / ^4.0 (L10–L13) | **keep 1.0.4** (not required; artisan-only; policy D-S1-8) |
| **D-S2-4** | laravel/framework: lock 10.50.3 (latest 10.x, security hardening only) or pin 10.48.29 | **10.50.3** |
| **D-S2-5** | add `Models/DateCastsTest` (2t) pinning the date behaviour of all 5 models (Advertismentboards and Announcements dates have no test today) | **yes** |
| **D-S2-6** | Symfony: keep 6.4 LTS (explicit package list, no `-W`) | **yes** |
| **D-S2-7** | Docker base `php:8.2-apache-bookworm` (same Debian as step 1, O5 still open) | **yes** |
| **D-S2-8** | commit order with the casts on L9 before the framework (2b before 2f) | **yes** |
| D-S2-9 | optional L10 skeleton modernisation (`$middlewareAliases`, return types in `app/`, `minimum-stability: stable`) | **no** (not required) |

No item needs a change to the public site, module behaviour (beyond the 5 casts), the database or a test.

Housekeeping: downloaded sources and the trial install are in the app container's `/tmp/s2` (not in the repo, gone with the container); golden-master captures are in the git-ignored `storage/golden-master/`.
