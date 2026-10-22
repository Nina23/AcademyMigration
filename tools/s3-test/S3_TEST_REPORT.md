# S3 test report — MinIO, pre-upgrade code vs upgrade step 1

Date: 2026-10-22. UPGRADE_PLAN.md D8, O1, O4, §2.3, §8 "S3 checks".
How to run it again: `tools/s3-test/README.md`.

## Setup

| | Reference run (`old-code`) | Step 1 run (`step-1`) |
|---|---|---|
| Branch | `development` (19d4a56) | `upgrade/step-1` (with the 1e fix, f2ab694) |
| PHP / Laravel | 7.4.33 / 8.83.29 | 8.1.34 / 9.52.22 |
| S3 adapter | Flysystem 1, `league/flysystem-aws-s3-v3` 1.x | Flysystem 3, `league/flysystem-aws-s3-v3` 3.32.0 |
| AWS SDK | `aws/aws-sdk-php` 3.337.3 | 3.337.3 (pinned) |
| Croppa | 4.11, crops on the default disk | 8.0.1, `crops_disk` = `croppa_crops` (D-S1-1 option c), `tmp_disk` = `local` |

Common to both runs:

- **MinIO** RELEASE.2026-09-22T19-25-18Z (Chainguard build of the open-source server; the official `minio/minio` images can no longer be pulled), pinned by digest in `docker-compose.s3.yml`. Bucket `akademija-s3test` with anonymous `download` policy; the app uses its own MinIO user, limited to that bucket (`app-policy.json`). Dummy credentials generated locally in `.env.s3test` (git-ignored, not printed).
- **App container `app-s3`** (same Dockerfile as `app`, own image and vendor volume) with `.env.s3test` as `.env`: `FILESYSTEM_DRIVER=s3`, `AWS_ENDPOINT=http://localhost:9000`, `AWS_URL=http://localhost:9000/akademija-s3test`, `AWS_USE_PATH_STYLE_ENDPOINT=true`, `APP_URL=http://localhost:8081`, `APP_DEBUG=false`, `CACHE_DRIVER=array`, `LOG_CHANNEL=stderr`. The local `public` disk is hidden (tmpfs) so nothing is served from dev uploads.
- **Database** `akademija_s3test`, re-imported from `backups/akademija_production.sql` before each run (same IDs in both runs). `akademija` and `akademija_testing` were not touched.
- **Checks** `run-checks.php` through real HTTP requests to the app (admin API with a temporary superuser created in `akademija_s3test` and deleted afterwards, admin session login, public page), S3 state read directly from MinIO, every S3 request of the app recorded with `mc admin trace`.
- No production bucket, no production credentials, no production database. No application code, module, view, route or test was changed for the test; S3 is switched on only by the env file.

Not identical between the runs by construction:

- `AWS_USE_PATH_STYLE_ENDPOINT=true` only exists on step 1 (`config/filesystems.php`, step 1c-4). The Laravel 8 S3 client therefore used virtual-host requests (`akademija-s3test.localhost:9000`, MinIO `MINIO_DOMAIN=localhost`), step 1 path-style requests. Production does not set the variable (default `false`, virtual-host style on both). This only changes the host form of presigned URLs and of the requests, not which objects are read or written.

## Results

| Check | Old code (L8) | Step 1 (L9) | Same? |
|---|---|---|---|
| **a** upload image `S3 Test Photo.jpg` | 200; object `files/s3-test-photo.jpg`, `Content-Type: image/jpeg`, size = upload; DB row `type i`, 800×600, `image/jpeg` | identical | same |
| **a** upload PDF | 200; `files/s3-test-document.pdf`, `application/pdf`; DB row `type d`, no width/height | identical | same |
| **a** second upload with the same name | `files/s3-test-photo_1.jpg` | identical | same |
| **a** ACL sent on upload (trace) | every `PutObject` (uploads, crop, `img-not-found`) sends `x-amz-acl: public-read` and the correct `Content-Type` | identical (same 7 `PutObject`s, same headers) | same |
| **a** ACL / visibility read back | `GetObjectAcl`: owner `FULL_CONTROL` only; `Storage::getVisibility()` = `private` (MinIO ignores per-object ACLs, see below) | identical | same |
| **a** object readable without credentials | 200 (bucket policy) | identical | same |
| **b** `Storage::url()` | `http://localhost:9000/akademija-s3test/files/…` (`AWS_URL` + key); API `url` the same | identical | same |
| **b** admin file form (`/admin/files/{id}/edit`) link | 200, link = `Storage::url()`, opens (200, right type) for image and PDF | identical | same |
| **b** public `_documents` link (`/akademija/dokumenti`, PDF attached in the DB copy) | page 200, visible link = `Storage::url()`, opens (200 `application/pdf`) | identical | same |
| **c** `Storage::temporaryUrl()` (`_documents`, inside the HTML comment) | presigned `GetObject`, `X-Amz-Algorithm=AWS4-HMAC-SHA256`, **`X-Amz-Expires=300`**; opens 200; after 305 s **403 `AccessDenied: Request has expired`** | identical (URL path-style, see above) | same |
| **d1** uploaded image through Croppa (`/storage/files/s3-test-photo-240x180.jpg?token=…`), 1st request | crop rendered from the S3 source, stored as `files/s3-test-photo-240x180.jpg` (240×180, `image/jpeg`, `public-read`); **`301 Location: /files/s3-test-photo-240x180.jpg`** | crop identical; **`301 Location: http://localhost:8081/files/s3-test-photo-240x180.jpg`** | same target (absolute instead of relative, D-S1-1 c) |
| **d1** redirect target | **404** (app page) | **404** | same (see O2c) |
| **d1** 2nd request | 301, same `Location`, crop reused (same ETag and modification time) | identical | same |
| **d1** local temp files | none left | none left (`tmp_disk` cleaned) | same |
| **d2** core presenter / API `thumb` (announcement with the uploaded image) | `…/storage/img-not-found-_x54.png?token=…` (the `is_file()` quirk, §2.3); `img-not-found.png` copied to the bucket; 1st request 301 → `/img-not-found-_x54.png` (404), crop stored, reused | identical, `Location: http://localhost:8081/img-not-found-_x54.png` (404) | same |
| **e** file-manager list `/api/files` (root) | 200; `thumb_sm` = original S3 URL, opens; folder `url` = `AWS_URL/` (by code and `FilesS3UrlTest` on L8) | **before 1e: 500** (`TypeError`, see below); **after 1e:** 200, `thumb_sm` identical and opens, folder `url` = `http://localhost:9000/akademija-s3test/` | **same after the fix** |
| **f** delete the image in the admin | 200; DB row gone; `files/s3-test-photo.jpg` **and** `files/s3-test-photo-240x180.jpg` removed; `_1` and PDF deleted too | identical (same `DeleteObject`s) | same |
| **g1** upload with MinIO stopped | **500** `Server Error`; **0 DB rows**; nothing in the bucket. Log: AWS error at `ListObjectsV2` (connection refused) | **500**; **0 DB rows**; nothing in the bucket. Log: `UnableToCheckFileExistence` | same outcome |
| **g2** upload with the app credentials rejected (MinIO user disabled) | **500**; **0 DB rows**; nothing in the bucket. Log: AWS 403 at `PutObject` | **500**; **0 DB rows**; nothing in the bucket. Log: `UnableToCheckFileExistence` | same outcome |
| **h** admin list `/api/announcements?…&append=thumb` | 200, 50 rows, all with `thumb` (`img-not-found` crop URL) | identical | same |

S3 requests made by the app (trace): the same 7 `PutObject` and 5 `DeleteObject` requests with the same keys, `Content-Type` and ACL on both. Reads differ only in number (old 136 `HeadObject` / 27 `ListObjectsV2`, step 1 119 / 18, both 17 `GetObject`): Flysystem 3 and Croppa 8 check existence differently. Both send one `HeadObject` for `img-not-found.png` per row of an admin list with thumbnails (102–103 for two 50-row requests; existing core-presenter behaviour, Stage 2).

## Differences and their explanation

1. **Step 1 regression, fixed — `/api/files` 500 on S3 (check e).** Folders are `files` rows with `type = 'f'` and `path = NULL` (728 in the production data; 34 in the root folder). `File::$appends` contains `url` = `Storage::url($this->path)`. Laravel 8 (Flysystem 1) returned the base URL (`AWS_URL/`) for `null`. Laravel 9's `Illuminate\Filesystem\AwsS3V3Adapter::url()` passes it to `PathPrefixer::prefixPath(string $path)` → `TypeError`, an `Error` that `File::getUrlAttribute()` does not catch (`catch (Exception $e)`). Every folder listing that contains sub-folders — the admin file manager and the file picker of every module — returned 500. On the local disk the same call only raises a PHP 8.1 deprecation (logged), so the suite and the golden master could not see it.
   **Fix (approved option 1):** commit 1e-1 adds `tests/Feature/Api/FilesS3UrlTest.php` (dummy `s3` disk, no network: 200 and folder `url` = `AWS_URL/`; passes on Laravel 8.83, fails on step 1); commit 1e-2 registers the `s3` driver in `AppServiceProvider` as `App\Filesystem\AwsS3V3Adapter`, a subclass whose `url()` turns `null` into `''` (the disk itself is built by `createS3Driver()` with the unchanged configuration). `Modules/` unchanged. After the fix check e matches the reference; full suite 68 tests, 501 assertions, OK. **The subclass must be checked in every later step (L10–L13)** against the framework class (UPGRADE_PLAN R22).
2. **Croppa redirect is absolute on step 1** (d1, d2): `http://localhost:8081/files/…` instead of `/files/…`. Intended (D-S1-1 option c, `croppa_crops` disk with `url = APP_URL`): same target, built from `APP_URL` (production: O2d). **The target returns 404 on both branches** — nothing in this repository serves `/<cropPath>` on the app host. Whether production serves it (web server rule, CDN, …) is not visible locally: **O2(c) stays open.** If production also answers 404, thumbnails of the core-presenter modules are broken today on both versions; changing that would be a deliberate behaviour change (UPGRADE_PLAN §2.3).
3. **Error path on S3 failure** (g): Laravel 9 fails one step earlier — `FileUploader`'s name check (`has()`) now throws `UnableToCheckFileExistence` instead of returning `false` — while Laravel 8 failed at `ListObjectsV2` (MinIO down) or `PutObject` (403). Outcome identical: 500, nothing saved, nothing uploaded. No change needed (`'throw' => true` keeps the write path throwing as well).
4. **Temporary URL host form** (c) and the XML error body sizes: path-style vs virtual-host requests (setup, see above). Not a code difference; production uses virtual-host style on both.

No other differences.

## What MinIO cannot reproduce (→ separate non-production AWS test bucket)

- **Per-object ACLs:** MinIO accepts `x-amz-acl: public-read` and ignores it; `GetObjectAcl` always returns only the owner's `FULL_CONTROL`, so `Storage::getVisibility()` says `private`. Objects are readable here because of the bucket policy, so an object uploaded *without* the ACL would be readable too. The trace proves that both versions *send* `public-read`; that this is what makes production objects public can only be checked on AWS.
- **Object Ownership** ("Bucket owner enforced" rejects every request carrying an ACL with `AccessControlListNotSupported`) and **Block Public Access**: not in MinIO. Production setting: O2(b).
- **AWS specifics:** virtual-host DNS names (`<bucket>.s3.<region>.amazonaws.com`) and `getObjectUrl()` without `AWS_URL` (O2a), region / SigV4 details, default CRC32 checksum handling, AWS error codes, the IAM policy of the production user.

## Effect on the open items (UPGRADE_PLAN.md)

- **O1:** MinIO part done for the pre-upgrade code and step 1; still open for steps 2–5 and for the AWS test bucket. Keep the `aws/aws-sdk-php` 3.337.3 pin until the AWS test bucket run.
- **O4:** confirmed on MinIO (presigned URL, 300 s, refused after expiry); re-checked with O1 in each step.
- **O2 (production checks)** and **O3** stay open; O2(c) in particular (redirect target 404 locally on both branches).
