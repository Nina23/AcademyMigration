# Test server (Ubuntu 24.04) — Docker setup and procedure

Files: `docker-compose.test-server.yml` (project **`akademija-test`**), `docker/test-server/Dockerfile`
(+ `Dockerfile.dockerignore`, `mpm_prefork.conf`), `.env.test-server.example`. The dev setup
(`docker-compose.yml`, `docker/php/Dockerfile`) is not used and not changed.

**The server also runs another live application, `fpl-live` (Docker, ports 80/443, `/opt/fpl-live`).
Nothing here may touch it:** every Docker command below names the project `akademija-test` (through
the `dc` function), nothing is run in `/opt/fpl-live`, and the commands in "Never" are not used.

| | |
|---|---|
| App | PHP 8.4.27 / Apache, Laravel 13.39.0, code **baked into the image** (no bind mount), `composer install --no-dev` from the committed lock (no Debugbar, Ignition, PHPUnit in the image), `APP_DEBUG=false`. Published **only on `127.0.0.1:8080`**. Memory limit 1 GB |
| Database | MariaDB 10.11.19 (the production version), one database (`DB_DATABASE`, e.g. `akademija_test`), **no published port**, own volume. Memory limit 1 GB |
| Network / volumes | `akademija-test_internal`, `akademija-test_storage` (app `storage/`), `akademija-test_db_data` |
| Access | SSH tunnel: `ssh -L 8080:127.0.0.1:8080 <user>@<server>`, then `http://localhost:8080` (= `APP_URL`) |
| Files / mail | a separate **non-production** S3 bucket; `MAIL_MAILER=log` (mails go to `storage/logs/laravel.log`) |
| Assets | no npm: the built `public/js/*.js`, `public/css/*.css`, `public/components/ckeditor4`, `public/css/template`, `public/js/template`, images and fonts are in git and in the image (the build checks the four main bundles). **But `public/mix-manifest.json` is not in git** — see the open decision below |

## Open decision: `APP_ENV` and `public/mix-manifest.json`

The admin and public layouts (`resources/views/vendor/core/{admin,public}/master.blade.php`) call
`mix('…')` **only when `APP_ENV=production`**, otherwise `asset('…')`. `mix()` reads
`public/mix-manifest.json`, which is **git-ignored and not in the repository**; without it every page
answers 500. The local copy (from the initial project, 2026-09-28) **does not match the committed
bundles** (`admin.js`, `admin.css`, `public.js`, `public.css` have other hashes), so it cannot simply
be added. Production must have its own manifest (or it would answer 500 today).

- **(a) recommended for the test server now: `APP_ENV=staging`** (the example's default). The layouts
  use `asset()`: same files, the only HTML difference from production is the missing `?id=…`
  cache-busting query on those four asset URLs. Nothing else in the code checks the environment
  (searched `app/`, `config/`, `routes/`, views, `Modules/`, `packages/`).
- (b) `APP_ENV=production` with a manifest. That needs a `public/mix-manifest.json` that matches the
  deployed bundles. It cannot come from git; generating it means npm (not allowed) or writing a build
  output by hand — a frontend build change that needs your approval.
- Related, for production (read-only, by you): compare production's `public/mix-manifest.json` and the
  md5 of its `public/js/admin.js`, `public/css/admin.css`, `public/js/public.js`, `public/css/public.css`
  with the committed files, to know whether production serves the same bundles as the repository.

## How the code gets to the server — recommendation: (B)

| | (A) `git clone` on the server, build there | **(B) image built locally, transferred** |
|---|---|---|
| Access needed | read access to the GitHub repository from the server (if private: a **read-only deploy key** for this one repository) | none: no repository access and no credentials on the server |
| Build load on the server | `apt`, `composer install` and the image build run next to `fpl-live` (CPU, memory, network; the build is not covered by the containers' memory limits) | none (only `docker load`) |
| What runs | whatever the server builds from the commit | **exactly the image built and checked locally**, tagged with the commit |
| Cost | — | ~0.8 GB image (smaller compressed) copied per version |

**(B) is recommended**: nothing to authorise on the server, no build next to the live application,
and the tested image is what runs. Build it from `git archive` of the commit (the committed bytes
only; no uncommitted or untracked files, no local `vendor/`).

## 1. Local: build and package the image (developer machine)

Who: you, on the machine with Docker and the repository. Nothing here connects to the server.

```bash
git fetch origin && git checkout <commit to deploy> && git status   # must be clean
SHA=$(git rev-parse --short HEAD)
mkdir -p /tmp/akademija-test-$SHA
git archive --format=tar HEAD | tar -x -C /tmp/akademija-test-$SHA   # committed files only
docker build --platform linux/amd64 \
  -f /tmp/akademija-test-$SHA/docker/test-server/Dockerfile \
  -t akademija-test/app:$SHA /tmp/akademija-test-$SHA
docker save akademija-test/app:$SHA | gzip > akademija-test-app-$SHA.tar.gz
sha256sum akademija-test-app-$SHA.tar.gz > akademija-test-app-$SHA.tar.gz.sha256
```

The build fails on purpose if Debugbar is installed or in the package cache, or if a main bundle in
`public/` is missing. Check `uname -m` on the server first (step 2): the image is `linux/amd64`.

Files to copy to the server: `akademija-test-app-$SHA.tar.gz` (+ `.sha256`),
`docker-compose.test-server.yml`, `.env.test-server.example`, `docker/test-server/README.md`.

## 2. Server: one-time preparation (read-only checks first)

Who: your SSH user on the server, a member of the `docker` group (or with `sudo` for `docker`).
Note: Docker access is root-equivalent — use only the commands below.

```bash
uname -m                                   # x86_64 (= linux/amd64)
docker version --format '{{.Server.Version}}'; docker compose version   # Compose v2
# fpl-live: look only (record its containers to compare after the deploy)
docker ps --filter label=com.docker.compose.project=fpl-live --format '{{.ID}} {{.Names}} {{.Status}} {{.Ports}}'
ss -ltnp | grep -E ':8080\b' || echo "8080 free"     # must be free
docker ps -a --filter label=com.docker.compose.project=akademija-test --format '{{.Names}}'   # empty on the first deploy
docker volume ls --filter name=akademija-test_; docker network ls --filter name=akademija-test_   # empty on the first deploy

sudo mkdir -p /opt/akademija-test && sudo chown "$USER": /opt/akademija-test    # NOT /opt/fpl-live
cd /opt/akademija-test
# copy the files from step 1 here (scp from your machine), then:
sha256sum -c akademija-test-app-<SHA>.tar.gz.sha256
gunzip -c akademija-test-app-<SHA>.tar.gz | docker load
cp .env.test-server.example .env.test-server && chmod 600 .env.test-server
# edit .env.test-server: APP_IMAGE_TAG=<SHA>, APP_KEY (echo "base64:$(openssl rand -base64 32)"),
# DB_PASSWORD, DB_ROOT_PASSWORD, MAIL_FROM_ADDRESS, the test bucket's AWS_* values
```

One env file serves both containers: the app container also receives `DB_ROOT_PASSWORD` (it does not
use it). Acceptable for a test server; keep the file `chmod 600`.

The `dc` helper (define it in every shell session on the server; every command below uses it):

```bash
dc() { docker compose --project-name akademija-test \
  --file /opt/akademija-test/docker-compose.test-server.yml \
  --env-file /opt/akademija-test/.env.test-server "$@"; }
dc config --quiet && echo "config OK"
dc config | grep -B1 -A6 'ports:'    # exactly one entry, under app: host_ip: 127.0.0.1, target: 80, published: "8080"
```

## 3. Server: deploy (in this order)

| # | Who | Command | Why |
|---|---|---|---|
| 1 | SSH user | `dc up -d db` | start MariaDB; on an empty volume it creates `DB_DATABASE` and `DB_USERNAME` |
| 2 | SSH user | `dc ps db` until `healthy` | |
| 3 | SSH user | **database import — section 4** (first deploy, or when resetting the copy) | |
| 4 | SSH user | `dc up -d --no-build app` | start the app from the loaded image (`--no-build`: never build on the server) |
| 5 | SSH user → container **www-data** | `dc exec -u www-data app php artisan optimize:clear` | config / routes / views / events / compiled caches |
| 6 | → **www-data** | `dc exec -u www-data app php artisan cache:clear` | **mandatory** (genealabs 13 cache keys; also after a rollback, UPGRADE_PLAN O6) |
| 7 | → **www-data** | `dc exec -u www-data app php artisan permission:cache-reset` | O6 |
| 8 | → **www-data** | `dc exec -u www-data app php artisan view:clear` | then warm up before use (O9): |
| 9 | SSH user | `for u in / /sr/login /en/login /sr-latn/login /en/password/reset /sitemap.xml; do curl -s -o /dev/null -w "$u %{http_code}\n" http://127.0.0.1:8080$u; done` | compiles the views of the first pages one at a time |
| 10 | SSH user | checks — section 5 | |

**Never on this server:** `php artisan migrate` (or any `migrate:*`, `db:wipe`, `db:seed`),
`route:cache`, `view:cache`, `config:cache` is not needed either; artisan as root (always
`-u www-data`, UPGRADE_PLAN R24); `docker system prune`, `docker volume prune`, `docker network prune`,
`docker image prune -a`, `docker compose down` / `up` without `--project-name akademija-test` (use
`dc`), `docker rm` / `docker stop` with a list of all containers, anything in `/opt/fpl-live`.

Stop / remove **only this project**: `dc stop`; `dc down` (containers and the
`akademija-test_internal` network; the volumes stay). Removing the data on purpose:
`dc down` then `docker volume rm akademija-test_db_data` (or `akademija-test_storage`) — by full name.

Update to a new version: steps 1 (local) and the `docker load` of step 2, set `APP_IMAGE_TAG` to the
new SHA, then steps 4–10 (`dc up -d --no-build app` recreates only the app container). Rollback = the
same with the old SHA (still loaded); `cache:clear` (step 6) is mandatory both ways.

## 4. Database import (only into this project's database)

Who: your SSH user (docker) — the import runs inside this project's `db` container, which holds no
other database. **No dump is assumed and none is taken from production by this procedure**: you
provide the file (e.g. an existing copy you are allowed to use), and you decide whether it may
contain personal data (user e-mails, password hashes) on a test server.

1. Check the file **before** importing: `grep -nE '^(CREATE DATABASE|USE |DROP DATABASE)' <dump.sql>`
   must print nothing (the import must not create, switch or drop a database); look at the head of the
   file (`head -40`) and its size.
2. Confirm the target (only `DB_DATABASE` besides the system schemas):
   `dc exec db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -N -e "SHOW DATABASES; SELECT @@version"'`
   → `information_schema`, `akademija_test` (your `DB_DATABASE`), `mysql`, `performance_schema`, `sys`; `10.11.19-MariaDB…`.
3. If the database is not empty (re-import): make it empty first, **only this one**:
   `dc exec db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "DROP DATABASE \`$MARIADB_DATABASE\`; CREATE DATABASE \`$MARIADB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL ON \`$MARIADB_DATABASE\`.* TO \`$MARIADB_USER\`@\`%\`;"'`
4. Import (from the host; the file is streamed into the container):
   `dc exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < <dump.sql>`
   (for a `.sql.gz`: `gunzip -c <dump.sql.gz> | dc exec -T db sh -c '…same…'`).
5. Check: `dc exec db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE(); SELECT COUNT(*) FROM typicms_pages; SELECT COUNT(*) FROM typicms_users;"'`
6. **Never run migrations** afterwards (5 migrations show as pending although their tables exist; CLAUDE.md).
7. Then deploy steps 5–9 (caches are empty after an import, but `cache:clear` is cheap and safe).

The `files` rows of the copy point at objects of the production bucket; the **test bucket starts
empty**, so existing images and documents will not load until you put test copies of some objects
there (your decision; never by reading credentials of the production bucket on the test server). New
uploads made during the checks go to the test bucket.

## 5. Post-deploy checks

**a. Isolation and configuration** (SSH user):

```bash
ss -ltnp | grep -E ':8080\b'              # only 127.0.0.1:8080
docker port akademija-test-app-1          # 80/tcp -> 127.0.0.1:8080
docker port akademija-test-db-1           # nothing
docker ps --filter label=com.docker.compose.project=fpl-live --format '{{.ID}} {{.Names}} {{.Status}}'   # same IDs as before, still up
dc exec -u www-data app php artisan about --only=environment   # Environment staging (see decision), Debug Mode OFF, URL localhost:8080
```

**b. Debugbar not active** (UPGRADE_PLAN O5):

```bash
dc exec app sh -c 'test ! -d vendor/barryvdh/laravel-debugbar && echo "debugbar not installed"'
curl -s http://127.0.0.1:8080/ | grep -cE 'Laravel Debugbar Widget|phpdebugbar'      # 0
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8080/_debugbar/open         # 404
```
and in the browser (through the tunnel), logged in: the page source of an admin page contains
neither string.

**c. O9 — cold compiled views** (UPGRADE_PLAN O9; read-only GET requests). The golden-master tool is in
the image; it runs inside the app container as www-data and writes to `storage/golden-master/` in the
storage volume (the captures contain data of the copy: delete them afterwards).

```bash
TOKEN=$(dc exec -T db sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" -N -e "SELECT api_token FROM typicms_users WHERE superuser=1 ORDER BY id LIMIT 1"')
cap() { dc exec -T -u www-data -e GM_API_TOKEN="$TOKEN" app php tools/golden-master/capture.php "$@" --base=http://127.0.0.1:80; }
dc exec -u www-data app php artisan view:clear
cap o9-warmup > /dev/null; dc exec -u www-data app rm -rf storage/golden-master/o9-warmup   # warm-up (discarded)
cap o9-warm                                                 # warm baseline
for i in 1 2 3; do dc exec -u www-data app php artisan view:clear; cap o9-cold$i --parallel=6; \
  dc exec -T -u www-data app php tools/golden-master/compare.php o9-warm o9-cold$i | head -12; done
for i in 4 5; do dc exec -u www-data app php artisan view:clear; cap o9-cold$i --parallel=12; \
  dc exec -T -u www-data app php tools/golden-master/compare.php o9-warm o9-cold$i | head -12; done
# the logo on /sr/login in every round (its body number is looked up in each capture's index.tsv)
dc exec -T -u www-data app sh -c 'for d in storage/golden-master/o9-*; do n=$(awk -F"\t" "\$6==\"/sr/login\"{print \$1}" $d/index.tsv); \
  printf "%s /sr/login logo: %s\n" "$d" "$(grep -c "class=\"logo\"" $d/bodies/$(printf %04d $n).txt)"; done'
dc exec -u www-data app rm -rf storage/golden-master                                         # afterwards
```
Expected: `same: 429` in every round and `logo: 1` for every capture. Any 500 (`File does not
exist at path …/views/…`), `logo: 0`, or any other difference is a finding — record it in O9.

**d. Manual checks — UPGRADE_PLAN.md §18.4 (yours, in the browser through the tunnel):**
A. saving in the admin, per module (incl. Pages sections: list, position + refresh, add, edit,
delete; News: "Add files", save, refresh, remove, save, refresh; Users R16; Settings; Roles; Files);
B. the contact form with `MAIL_MAILER=log` (check `dc exec -u www-data app tail -n 60 storage/logs/laravel.log`);
C. file-manager thumbnails (the test bucket, see section 4 about existing objects).
Also O7 later (Mailgun sandbox, never the production domain).
