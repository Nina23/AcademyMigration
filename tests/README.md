# Baseline regression suite

A small safety net to run **before and after every upgrade milestone**
(Laravel 8.30 → 8.83 → PHP 8.1 → Laravel 9 … 12/13, PHP 8.3+).
It answers: *does the application still behave like it did before?*

The application as it was on 2026-09-29 is the reference behaviour. Tests pin
current behaviour — including a few known bugs, clearly marked `known_bug` /
"KNOWN" — rather than intended behaviour.

## Running

```bash
docker compose up -d                                   # app, mariadb (dev), mariadb-test
docker compose exec app php artisan test               # or: php vendor/bin/phpunit
docker compose exec app php vendor/bin/phpunit tests/Feature/Translations   # one area
docker compose exec -e TEST_DB_REBUILD=0 app php vendor/bin/phpunit         # skip the DB rebuild
```

## Test database (isolation)

| | Server | Database |
|---|---|---|
| Development | `mariadb` service (MariaDB 10.11.19) | `akademija` (imported from the production dump) |
| Tests | `mariadb-test` service (MariaDB 10.11.19, tmpfs) | `akademija_testing` |

- `tests/bootstrap.php` rebuilds `akademija_testing` once per run: wipe →
  load `tests/Fixtures/schema/akademija_schema.sql` → `Tests\Fixtures\BaselineSeeder`.
  No production data is ever loaded.
- `tests/Support/TestDatabaseGuard.php` aborts unless `APP_ENV=testing`, the
  database name ends in `_testing` and the server is `10.11.19-MariaDB`
  (checked in the bootstrap and in every test's `setUp`). `phpunit.xml` pins
  `DB_HOST`/`DB_DATABASE`, which overrides environment variables.
- Every test runs inside a transaction that is rolled back.
- Why a schema file and not migrations: `migrate:fresh` fails on an empty
  database (news migration references categories before it exists), and the
  migrations do not match the production schema.
- Regenerate the schema from a new production dump:
  `php tests/Support/extract-production-schema.php backups/<dump>.sql tests/Fixtures/schema/akademija_schema.sql`
  (drops INSERTs and AUTO_INCREMENT counters only; everything else verbatim).
- `BaselineSeeder` holds only what the app reads **while booting**: settings
  (sr, sr-latn, en), the home page and the `news`/`classschedules` module pages
  (their public routes only exist when such a page exists), plus a category and
  an announcement category/department. Everything else is created per test.

## What is covered

| Area | Files | Tests |
|---|---|---|
| Infrastructure | `Feature/Infrastructure` | 3 |
| Boot / container / routes | `Feature/Boot` | 5 |
| Public routes (DB-driven routing, locales, views) | `Feature/PublicRoutesTest.php` | 8 |
| Authentication | `Feature/Auth` | 5 |
| Admin access + News admin CRUD | `Feature/Admin` | 7 |
| Models: News, Classschedules | `Feature/Models` | 8 |
| **Translations** (storage, fallback, JSON queries) | `Feature/Translations` | 13 |
| Images / uploads / Croppa | `Feature/Images` | 7 |
| API (News, Pages, Classschedules) | `Feature/Api` | 8 |
| Laravel examples (unchanged) | `Unit`, `Feature/ExampleTest.php` | 2 |

## Test-harness notes (not application bugs)

A test reuses one application instance for several requests; production uses a
fresh one per request. Three singletons are therefore reset where needed:
the token guard's cached user (`TestCase::apiHeaders()`), Croppa's request-bound
`Handler`/`Helpers`, and the filesystem singletons after `Storage::fake()`
(`Images/FileUploadTest`).

## TODO — Stage 2

| Priority | Task | Suite follow-up |
|---|---|---|
| **HIGH** | Fix the SQL injection in the admin list search (`FilterOr`, known issue #3): bind the search value instead of concatenating it into raw SQL. Related, lower priority: the public search (`typicms/search` PublicController) also builds raw `LIKE` SQL but escapes words with `addslashes()` (quotes verified to return 200) — replace with bindings while there. | Add a test that a search containing `'` returns 200 and matches literally. |
| Normal | Remove the unused news RSS feed completely (route, `PublicController::feed()`, `laravelium/feed` if nothing else uses it). This also resolves known issue #1. | Delete the two feed tests in `Feature/PublicRoutesTest.php`, remove `'en::news-feed'` from `Feature/Boot/ApplicationBootTest.php`, drop issue #1 below. |

## Known issues found while building the suite (NOT fixed)

| # | Issue | Status in suite |
|---|---|---|
| 1 | News RSS feed returns **500** as soon as one news item exists: `PublicController::feed()` calls `$feed->add()`, `laravelium/feed` v8.0.1 only has `addItem()`. Affects production. The feed is unused and will be removed in Stage 2 (not fixed). | Pinned: `test_known_bug_news_feed_with_items_returns_500` |
| 2 | Missing / bad-token Croppa images return **500** instead of 404 (error view uses `$errors`, not shared on the Croppa route). Verified on the dev app. | Documented |
| 3 | Admin list search (`FilterOr`) concatenates the search value into raw SQL → **SQL injection** for authenticated admins; a `'` in the search causes a 500. **HIGH priority, Stage 2.** | Documented |
| 4 | `SlugObserver` "slug from title" branch throws *Undefined index* when slugs are empty (Spatie drops empty translations). Unreachable from the admin (validation). | Documented |
| 5 | Empty/null translations are silently dropped from the JSON when an attribute is written (Spatie 4.6). | Pinned (current behaviour) |
| 6 | `Historable` "updated" listener reads `$model->original[$key]`; updating an attribute not set on the same in-memory instance that was just created throws. | Documented |
| 7 | Uploads with no `description`/`alt_attribute` → 500 (NOT NULL + `CHECK(json_valid)`); the admin always sends them. Empty/truncated uploads → 500 (`getimagesize()` read error). | Documented |
| 8 | One production announcement has `program = null`; `formatted_program` then raises an error (warning → exception) wherever it is rendered. | Documented |
| 9 | Migrations cannot build a fresh database (ordering) and differ from the production schema. | Documented |
| 10 | `UserFactory` references non-existent `App\Models\User`; stock seeders use en/fr/nl. | Documented |
| 11 | `ClassScheduleItems` overrides Eloquent's internal `$relations` property. No visible effect found. | Documented |
| 12 | Non-superuser roles/permissions are unused in production (no rows in `role_user`/`permission_user`; all active users are superusers). | Behaviour covered by tests |

Resolved / withdrawn: the unquoted `$.sr-latn` JSON path in TypiCMS raw SQL
fails on MySQL 8 but works on MariaDB 10.11 (production) — covered by
`TranslatableQueryTest`, will fail if the DB engine changes.

## Upgrade hot spots this suite watches

- Translations storage format: `json_encode` without flags → `\uXXXX` escapes,
  key order, dropped empty values, fallback to `en` on the site and none in the
  admin (Spatie Translatable v5/v6 change these areas).
- Raw MariaDB JSON SQL in TypiCMS (`selectFields`, `FilterOr`, search,
  translations loader) and Eloquent `column->sr-latn` paths.
- Flysystem 1 → 3 (Laravel 9): `Storage::url`, uploads, Croppa disk wiring.
- Auth scaffolding (laravel/ui) and Spatie Permission; token guard API auth.
- Pagination JSON shape and spatie/laravel-query-builder (fields/sort/filter/include).
- Laravel 11 adds a separate `mariadb` DB driver (`DB_CONNECTION` decision).
