# Local S3 test (MinIO)

Tests the Amazon S3 code path locally (UPGRADE_PLAN.md D8, O1, O4, §2.3, §8
"S3 checks"). Production stores every file on S3; dev and the test suite use the
local `public` disk, so this is the only place where the S3 path is exercised.

**MinIO only.** Own bucket (`akademija-s3test`), own dummy credentials, own
database copy (`akademija_s3test`). Never the production bucket, production AWS
credentials or the production database. No application code, module, view,
route or test is changed: S3 is switched on only by `.env.s3test`.

## What it consists of

| File | Purpose |
|---|---|
| `docker-compose.s3.yml` | adds `app-s3` (same Dockerfile as `app`, own image, own `vendor_s3` volume, http://localhost:8081) and `minio` (S3 API on `localhost:9000`, console on `localhost:9001`). MinIO shares the network namespace of `app-s3`, so `localhost:9000` is the same MinIO inside the container and on the host. The normal dev setup (`docker compose up`) does not use this file |
| `.env.s3test.example` | committed template with placeholders |
| `.env.s3test` | local, git-ignored, **never committed**: dummy MinIO credentials, `FILESYSTEM_DRIVER=s3`, `AWS_ENDPOINT`/`AWS_URL` to MinIO, `AWS_USE_PATH_STYLE_ENDPOINT=true`, `DB_DATABASE=akademija_s3test`. Mounted as `.env` in `app-s3` only |
| `tools/s3-test/setup.sh` | creates `.env.s3test`, starts the stack, installs dependencies, creates the bucket / DB copy |
| `tools/s3-test/run.sh` | one full checklist run (fresh bucket + DB, checks a–h, ~8 min) |
| `tools/s3-test/run-checks.php` | the checks; runs inside `app-s3` |
| `tools/s3-test/app-policy.json` | MinIO policy of the app user (the test bucket only) |
| `tools/s3-test/S3_TEST_REPORT.md` | results |

Results are written to `storage/app/s3-test/` (git-ignored).

## Start

```bash
bash tools/s3-test/setup.sh env        # once: .env.s3test with random dummy credentials (not printed)
bash tools/s3-test/setup.sh up         # build app-s3 for the checked-out branch, start app-s3 + minio
bash tools/s3-test/setup.sh composer   # composer install into the app-s3 vendor volume
bash tools/s3-test/setup.sh reset      # bucket (anonymous read, app user + policy) + akademija_s3test from the dump
```

After a branch switch, run `up` and `composer` again (the image and `vendor_s3`
follow the checked-out branch). The dev containers (`app`, `mariadb`,
`mariadb-test`) are not changed; `akademija_s3test` lives on the dev `mariadb`
server next to `akademija`, which is not touched.

## Run the checklist

```bash
bash tools/s3-test/run.sh <label>      # e.g. old-code, step-1
diff storage/app/s3-test/old-code-main.txt storage/app/s3-test/step-1-main.txt
```

`run-checks.php` refuses to run unless the database is `akademija_s3test`, the
default disk is `s3`, the endpoint is `http://localhost:9000` and the bucket is
`akademija-s3test`. It creates a temporary superuser in `akademija_s3test`
(random password and API token, in memory only) and deletes it at the end.
Output masks signatures, credentials, dates and Croppa tokens.

Checks: (a) uploads (image, duplicate name, PDF): object, `Content-Type`, ACL;
(b) `Storage::url()` links (admin file form, public `_documents` link);
(c) `Storage::temporaryUrl()` from `_documents` (expiry 300 s, refused after
expiry); (d) Croppa: an uploaded image through a Croppa URL, and the core
presenter / API `thumb`; (e) file-manager `thumb_sm`; (f) delete (source + crops);
(g) S3 failure during upload (MinIO stopped; app credentials rejected);
(h) admin list `append=thumb`. The S3 requests the app makes are recorded with
`mc admin trace` (operation, path, `Content-Type`, `x-amz-acl`, status).

MinIO client commands (no shell in the image, one command per run):

```bash
MSYS_NO_PATHCONV=1 docker run --rm --network "container:$(docker compose -f docker-compose.yml -f docker-compose.s3.yml ps -q app-s3)" \
  --env-file .env.s3test cgr.dev/chainguard/minio-client@sha256:f7fc12ca86b804be7f964260447acc4578fac730778117c8658ad96fab94bf8b ls -r s3test/akademija-s3test
```

## Stop

```bash
bash tools/s3-test/setup.sh down       # stops app-s3 and minio; volumes are kept
docker compose -f docker-compose.yml -f docker-compose.s3.yml rm -sf app-s3 minio
docker volume rm akademija_prod_2026_minio_data akademija_prod_2026_vendor_s3 akademija_prod_2026_composer_cache_s3   # optional
docker compose exec mariadb mariadb -uroot -proot -e 'DROP DATABASE akademija_s3test'                                  # optional
```

`app-s3` runs `composer install` / `package:discover` in the shared working tree,
which rewrites `bootstrap/cache/packages.php` and `services.php`. Back on the dev
setup, run `docker compose exec app php artisan package:discover` (or
`composer install`) before using `app` again.

## What MinIO cannot reproduce (check on a separate non-production AWS test bucket)

- **Per-object ACLs.** MinIO accepts the `x-amz-acl: public-read` header on
  upload but ignores it; `GetObjectAcl` always returns only the owner's
  `FULL_CONTROL`, so `Storage::getVisibility()` reports `private`. Public reading
  is granted here by a bucket policy (anonymous `download`), so an object uploaded
  without the ACL would also be readable. Whether `public-read` is what makes
  production objects public cannot be tested on MinIO.
- **Object Ownership** ("Bucket owner enforced" rejects every request with an
  ACL: `AccessControlListNotSupported`) and **Block Public Access** do not exist
  in MinIO.
- AWS-specific behaviour: virtual-host DNS names (`<bucket>.s3.<region>.amazonaws.com`),
  region / signature handling, default checksum headers (CRC32), error codes and
  messages, eventual consistency, IAM policies of the production user.
