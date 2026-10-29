# S3 test report — MinIO, pre-upgrade code vs upgrade steps 1, 2 and 3

Date: 2026-10-22 (reference, step 1), 2026-10-29 (steps 2 and 3). UPGRADE_PLAN.md D8, O1, O4, §2.3, §8 "S3 checks".
How to run it again: `tools/s3-test/README.md`.

## Setup

| | Reference run (`old-code`) | Step 1 run (`step-1`) | Step 2 run (`step-2`) | Step 3 run (`step-3`) |
|---|---|---|---|---|
| Branch | `development` (19d4a56) | `upgrade/step-1` (with the 1e fix, f2ab694) | `upgrade/step-2` (06f99cb, step 2f) | `upgrade/step-3` (6db999d, step 3e) |
| PHP / Laravel | 7.4.33 / 8.83.29 | 8.1.34 / 9.52.22 | 8.2.35 / 10.50.3 | 8.4.27 / 11.57.1 |
| S3 adapter | Flysystem 1, `league/flysystem-aws-s3-v3` 1.x | Flysystem 3, `league/flysystem-aws-s3-v3` 3.32.0 | = step 1 (unchanged in step 2) | = step 1 (unchanged in step 3; Symfony 7.4) |
| AWS SDK | `aws/aws-sdk-php` 3.337.3 | 3.337.3 (pinned) | 3.337.3 (pinned) | 3.337.3 (pinned) |
| Croppa | 4.11, crops on the default disk | 8.0.1, `crops_disk` = `croppa_crops` (D-S1-1 option c), `tmp_disk` = `local` | = step 1 | = step 1 |

Common to all runs:

- **MinIO** RELEASE.2026-09-22T19-25-18Z (Chainguard build of the open-source server; the official `minio/minio` images can no longer be pulled), pinned by digest in `docker-compose.s3.yml`. Bucket `akademija-s3test` with anonymous `download` policy; the app uses its own MinIO user, limited to that bucket (`app-policy.json`). Dummy credentials generated locally in `.env.s3test` (git-ignored, not printed).
- **App container `app-s3`** (same Dockerfile as `app`, own image and vendor volume, rebuilt for each branch) with `.env.s3test` as `.env`: `FILESYSTEM_DRIVER=s3`, `AWS_ENDPOINT=http://localhost:9000`, `AWS_URL=http://localhost:9000/akademija-s3test`, `AWS_USE_PATH_STYLE_ENDPOINT=true`, `APP_URL=http://localhost:8081`, `APP_DEBUG=false`, `CACHE_DRIVER=array`, `LOG_CHANNEL=stderr`. The local `public` disk is hidden (tmpfs) so nothing is served from dev uploads.
- **Database** `akademija_s3test`, re-imported from `backups/akademija_production.sql` before each run (same IDs in every run). `akademija` and `akademija_testing` were not touched.
- **Checks** `run-checks.php` through real HTTP requests to the app (admin API with a temporary superuser created in `akademija_s3test` and deleted afterwards, admin session login, public page), S3 state read directly from MinIO, every S3 request of the app recorded with `mc admin trace`.
- No production bucket, no production credentials, no production database. No application code, module, view, route or test was changed for the test; S3 is switched on only by the env file.

Not identical between the runs by construction:

- `AWS_USE_PATH_STYLE_ENDPOINT=true` only exists from step 1 on (`config/filesystems.php`, step 1c-4). The Laravel 8 S3 client therefore used virtual-host requests (`akademija-s3test.localhost:9000`, MinIO `MINIO_DOMAIN=localhost`), steps 1 and 2 path-style requests. Production does not set the variable (default `false`, virtual-host style on all versions). This only changes the host form of presigned URLs and of the requests, not which objects are read or written.

## Results

| Check | Old code (L8) | Step 1 (L9) | Step 2 (L10) | Step 3 (L11) | Same? |
|---|---|---|---|---|---|
| **a** upload image `S3 Test Photo.jpg` | 200; object `files/s3-test-photo.jpg`, `Content-Type: image/jpeg`, size = upload; DB row `type i`, 800×600, `image/jpeg` | identical | identical | identical to step 2 | same |
| **a** upload PDF | 200; `files/s3-test-document.pdf`, `application/pdf`; DB row `type d`, no width/height | identical | identical | identical to step 2 | same |
| **a** second upload with the same name | `files/s3-test-photo_1.jpg` | identical | identical | identical to step 2 | same |
| **a** ACL sent on upload (trace) | every `PutObject` (uploads, crop, `img-not-found`) sends `x-amz-acl: public-read` and the correct `Content-Type` | identical (same 7 `PutObject`s, same headers) | identical | identical to step 2 | same |
| **a** ACL / visibility read back | `GetObjectAcl`: owner `FULL_CONTROL` only; `Storage::getVisibility()` = `private` (MinIO ignores per-object ACLs, see below) | identical | identical | identical to step 2 | same |
| **a** object readable without credentials | 200 (bucket policy) | identical | identical | identical to step 2 | same |
| **b** `Storage::url()` | `http://localhost:9000/akademija-s3test/files/…` (`AWS_URL` + key); API `url` the same | identical | identical | identical to step 2 | same |
| **b** admin file form (`/admin/files/{id}/edit`) link | 200, link = `Storage::url()`, opens (200, right type) for image and PDF | identical | identical | identical to step 2 | same |
| **b** public `_documents` link (`/akademija/dokumenti`, PDF attached in the DB copy) | page 200, visible link = `Storage::url()`, opens (200 `application/pdf`) | identical | identical | identical to step 2 | same |
| **c** `Storage::temporaryUrl()` (`_documents`, inside the HTML comment) | presigned `GetObject`, `X-Amz-Algorithm=AWS4-HMAC-SHA256`, **`X-Amz-Expires=300`**; opens 200; after 305 s **403 `AccessDenied: Request has expired`** | identical (URL path-style, see above) | identical to step 1 | identical to step 2 | same |
| **d1** uploaded image through Croppa (`/storage/files/s3-test-photo-240x180.jpg?token=…`), 1st request | crop rendered from the S3 source, stored as `files/s3-test-photo-240x180.jpg` (240×180, `image/jpeg`, `public-read`); **`301 Location: /files/s3-test-photo-240x180.jpg`** | crop identical; **`301 Location: http://localhost:8081/files/s3-test-photo-240x180.jpg`** | identical to step 1 | identical to step 2 | same target (absolute instead of relative, D-S1-1 c) |
| **d1** redirect target | **404** (app page) | **404** | **404** (page 19 bytes larger: Laravel 10 `csrf_field()`, see difference 5) | identical to step 2 (404 page: `charset=utf-8`, D-S3-9) | same |
| **d1** 2nd request | 301, same `Location`, crop reused (same ETag and modification time) | identical | identical | identical to step 2 | same |
| **d1** local temp files | none left | none left (`tmp_disk` cleaned) | none left | identical to step 2 | same |
| **d2** core presenter / API `thumb` (announcement with the uploaded image) | `…/storage/img-not-found-_x54.png?token=…` (the `is_file()` quirk, §2.3); `img-not-found.png` copied to the bucket; 1st request 301 → `/img-not-found-_x54.png` (404), crop stored, reused | identical, `Location: http://localhost:8081/img-not-found-_x54.png` (404) | identical to step 1 (404 page 19 bytes larger, difference 5) | identical to step 2 (404 page: `charset=utf-8`, D-S3-9) | same |
| **e** file-manager list `/api/files` (root) | 200; `thumb_sm` = original S3 URL, opens; folder `url` = `AWS_URL/` (by code and `FilesS3UrlTest` on L8) | **before 1e: 500** (`TypeError`, see below); **after 1e:** 200, `thumb_sm` identical and opens, folder `url` = `http://localhost:9000/akademija-s3test/` | identical to step 1 after 1e (`App\Filesystem\AwsS3V3Adapter` unchanged, R22) | identical to step 2 | **same after the fix** |
| **f** delete the image in the admin | 200; DB row gone; `files/s3-test-photo.jpg` **and** `files/s3-test-photo-240x180.jpg` removed; `_1` and PDF deleted too | identical (same `DeleteObject`s) | identical | identical to step 2 | same |
| **g1** upload with MinIO stopped | **500** `Server Error`; **0 DB rows**; nothing in the bucket. Log: AWS error at `ListObjectsV2` (connection refused) | **500**; **0 DB rows**; nothing in the bucket. Log: `UnableToCheckFileExistence` | identical to step 1 | identical to step 2 | same outcome |
| **g2** upload with the app credentials rejected (MinIO user disabled) | **500**; **0 DB rows**; nothing in the bucket. Log: AWS 403 at `PutObject` | **500**; **0 DB rows**; nothing in the bucket. Log: `UnableToCheckFileExistence` | identical to step 1 | identical to step 2 | same outcome |
| **h** admin list `/api/announcements?…&append=thumb` | 200, 50 rows, all with `thumb` (`img-not-found` crop URL) | identical | identical (append patch on QB 6.4.4) | identical to step 2 | same |

S3 requests made by the app (trace): the same 7 `PutObject` and 5 `DeleteObject` requests with the same keys, `Content-Type` and ACL in all runs; **the step 3 trace is identical to step 2 (and step 1), line by line**. Reads differ only in number between L8 and L9 (old 136 `HeadObject` / 27 `ListObjectsV2`, step 1 119 / 18, both 17 `GetObject`): Flysystem 3 and Croppa 8 check existence differently. **The step 2 trace is identical to step 1, line by line** (119 `HeadObject`, 18 `ListObjectsV2`, 17 `GetObject`, 7 `GetObjectAcl`, 7 `PutObject`, 5 `DeleteObject`). All runs send one `HeadObject` for `img-not-found.png` per row of an admin list with thumbnails (102–103 for two 50-row requests; existing core-presenter behaviour, Stage 2).

Step 2 vs step 1, all result files (`storage/app/s3-test/step-1-*` vs `step-2-*`): identical except the version lines, log timestamps and the two 404 page sizes (difference 5).

Step 3 vs step 2 (`step-2-*` vs `step-3-*`): identical except the version lines, log timestamps and the charset case of the two 404 pages (`charset=UTF-8` → `charset=utf-8`, same sizes; difference 6). Step 3 run: `composer install --no-scripts` in `app-s3` (vendor volume), then `artisan clear-compiled` / `package:discover` and `run-checks.php` **as `www-data`** (a copy of `run.sh` with `exec -u www-data` for the checks), so the shared `storage/` and `bootstrap/cache` get no root-owned files (UPGRADE_PLAN R24). The committed scripts still run them as root.

## Differences and their explanation

1. **Step 1 regression, fixed — `/api/files` 500 on S3 (check e).** Folders are `files` rows with `type = 'f'` and `path = NULL` (728 in the production data; 34 in the root folder). `File::$appends` contains `url` = `Storage::url($this->path)`. Laravel 8 (Flysystem 1) returned the base URL (`AWS_URL/`) for `null`. Laravel 9's `Illuminate\Filesystem\AwsS3V3Adapter::url()` passes it to `PathPrefixer::prefixPath(string $path)` → `TypeError`, an `Error` that `File::getUrlAttribute()` does not catch (`catch (Exception $e)`). Every folder listing that contains sub-folders — the admin file manager and the file picker of every module — returned 500. On the local disk the same call only raises a PHP 8.1 deprecation (logged), so the suite and the golden master could not see it.
   **Fix (approved option 1):** commit 1e-1 adds `tests/Feature/Api/FilesS3UrlTest.php` (dummy `s3` disk, no network: 200 and folder `url` = `AWS_URL/`; passes on Laravel 8.83, fails on step 1); commit 1e-2 registers the `s3` driver in `AppServiceProvider` as `App\Filesystem\AwsS3V3Adapter`, a subclass whose `url()` turns `null` into `''` (the disk itself is built by `createS3Driver()` with the unchanged configuration). `Modules/` unchanged. After the fix check e matches the reference; full suite 68 tests, 501 assertions, OK. **The subclass must be checked in every later step (L10–L13)** against the framework class (UPGRADE_PLAN R22). Step 2: the framework class is byte-identical 9.52.22 → 10.50.3, check e identical.
2. **Croppa redirect is absolute from step 1 on** (d1, d2): `http://localhost:8081/files/…` instead of `/files/…`. Intended (D-S1-1 option c, `croppa_crops` disk with `url = APP_URL`): same target, built from `APP_URL` (production: O2d). **The target returns 404 on every branch** — nothing in this repository serves `/<cropPath>` on the app host. Whether production serves it (web server rule, CDN, …) is not visible locally: **O2(c) stays open.** If production also answers 404, thumbnails of the core-presenter modules are broken today on every version; changing that would be a deliberate behaviour change (UPGRADE_PLAN §2.3).
3. **Error path on S3 failure** (g): Laravel 9 and 10 fail one step earlier — `FileUploader`'s name check (`has()`) now throws `UnableToCheckFileExistence` instead of returning `false` — while Laravel 8 failed at `ListObjectsV2` (MinIO down) or `PutObject` (403). Outcome identical: 500, nothing saved, nothing uploaded. No change needed (`'throw' => true` keeps the write path throwing as well).
4. **Temporary URL host form** (c) and the XML error body sizes: path-style vs virtual-host requests (setup, see above). Not a code difference; production uses virtual-host style on all versions.
5. **Step 2: the 404 page of the Croppa redirect target is 19 bytes larger** (d1 52137 → 52156 B, d2 52128 → 52147 B). The check fetches it logged in as the temporary superuser, so the page contains the navbar logout form, and Laravel 10's `csrf_field()` (`Illuminate/Foundation/helpers.php`) adds ` autocomplete="off"` (19 bytes) to the hidden `_token` input. The only `csrf_field()` call in the project is the logout form in `core::_navbar` (`resources/views/vendor/core/_navbar.blade.php:39`, shown to logged-in users only); all other forms get their token from TypiCMS BootForm. Not S3-related; no visible or functional change (the golden master captures anonymous pages only, so it does not contain this form).

6. **Step 3: `Content-Type: text/html; charset=utf-8`** instead of `charset=UTF-8` on the HTML 404 pages (d1, d2): Symfony 7 `Response::prepare()` default. Charset names are case-insensitive; same as on every HTML page in step 3 (STEP3_PLAN D-S3-9). Not S3-related.

No other differences.

## What MinIO cannot reproduce (→ separate non-production AWS test bucket)

- **Per-object ACLs:** MinIO accepts `x-amz-acl: public-read` and ignores it; `GetObjectAcl` always returns only the owner's `FULL_CONTROL`, so `Storage::getVisibility()` says `private`. Objects are readable here because of the bucket policy, so an object uploaded *without* the ACL would be readable too. The trace proves that every version *sends* `public-read`; that this is what makes production objects public can only be checked on AWS.
- **Object Ownership** ("Bucket owner enforced" rejects every request carrying an ACL with `AccessControlListNotSupported`) and **Block Public Access**: not in MinIO. Production setting: O2(b).
- **AWS specifics:** virtual-host DNS names (`<bucket>.s3.<region>.amazonaws.com`) and `getObjectUrl()` without `AWS_URL` (O2a), region / SigV4 details, default CRC32 checksum handling, AWS error codes, the IAM policy of the production user.

## Effect on the open items (UPGRADE_PLAN.md)

- **O1:** MinIO part done for the pre-upgrade code and steps 1–3; still open for steps 4–5 and for the AWS test bucket. Keep the `aws/aws-sdk-php` 3.337.3 pin until the AWS test bucket run.
- **O4:** confirmed on MinIO (presigned URL, 300 s, refused after expiry) on all four runs; re-checked with O1 in each step.
- **O2 (production checks)** and **O3** stay open; O2(c) in particular (redirect target 404 locally on every branch).
