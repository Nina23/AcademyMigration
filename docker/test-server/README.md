# Test server — setup and operation (Ubuntu 24.04, Docker)

This guide assumes you have never run Docker on a server. Every command says what it does and what you
should see. Commands are run **as root** on the server (you log in as root), unless a step says
"on your computer". Inside the app container, `artisan` always runs as the web user `www-data`.

> **Read first**
> - **This test copy contains real user data** (the users and files rows of the dev database): use it
>   only for the testing period, then remove it (section 10).
> - **Everything goes over plain HTTP** (`http://SERVER_IP:8080`), including the Basic Auth password
>   and every login. Testers must **not** use a password they use anywhere else, and must not change
>   their test password to one.
> - **Basic Auth has two narrow exceptions** (decided 2026-11-04, option A): the admin's Vue code
>   sends its own `Authorization: Bearer <api_token>` header, and a request carries only one
>   `Authorization` header. So requests to `/api/…` **with** a Bearer header, and `GET
>   /admin/_locale/<language>` with one, go to the app without Basic Auth; the app checks the token
>   (all `/api` routes except `GET /api/tags-list`, the tag names) or the admin session. Everything else
>   needs Basic Auth (section 7b checks it).
> - **Another live application, `fpl-live`, runs on this server** (`/opt/fpl-live`, ports 80/443,
>   its own Caddy). Nothing here may touch it: always use the `dc` helper (section 3), never the
>   commands in section 9, and never work in `/opt/fpl-live`.

## 1. What runs, in plain words

| Word | Meaning here |
|---|---|
| image | a packaged, read-only copy of the application (PHP 8.4, Apache, the code, the PHP libraries). Built **on your computer** and copied to the server |
| container | a running instance of an image. There are three: `proxy`, `app`, `db` |
| volume | a folder managed by Docker that **survives** restarts and updates: `akademija-test_db_data` (the database), `akademija-test_storage` (uploads, logs, caches, sessions) |
| project | the group of containers, network and volumes named **`akademija-test`**; every command below is limited to it |

```
internet ──► SERVER_IP:8080 ──► proxy (Caddy: Basic Auth, noindex) ──► app (Apache + PHP, port 80 inside) ──► db (MariaDB 10.11.19)
                                 only this one publishes a port        no published port                   no published port
```

- Address for the testers: **`http://SERVER_IP:8080`** (= `APP_URL`; the published address can be
  changed with `PROXY_BIND` in `.env.test-server`, unset = `8080` on all interfaces). The browser first asks for the
  Basic Auth user and password (shared by all testers), then the site opens; the admin login is the
  site's normal login (`/sr/login`).
- Memory limits: app 1 GB, db 1 GB, proxy 128 MB (they cannot use more, so `fpl-live` keeps its memory).
- `APP_ENV=staging`, `APP_DEBUG=false`, `MAIL_MAILER=log` (no e-mail leaves the server: mails are
  written to the log), Debugbar is not installed in the image.
- **Files: local disk for now** (in the `akademija-test_storage` volume). **No files are copied**, so
  the existing images and documents of the copy **do not display** (broken image / 404). New uploads
  work. S3 comes later (section 11); until then S3 stays open (UPGRADE_PLAN O1, O2(c), O3) and the
  file checks must be repeated on the bucket.

## 2. On your computer: build the image, take the dump

Who: you, on the machine with Docker and the repository (nothing here connects to the server).

```bash
git fetch origin && git checkout deploy/test-server && git status   # "nothing to commit": must be clean
SHA=$(git rev-parse --short HEAD)                                   # the version name, e.g. de3af64
mkdir -p /tmp/akademija-test-$SHA
git archive --format=tar HEAD | tar -x -C /tmp/akademija-test-$SHA   # exactly the committed files
docker build --platform linux/amd64 \
  -f /tmp/akademija-test-$SHA/docker/test-server/Dockerfile \
  -t akademija-test/app:$SHA /tmp/akademija-test-$SHA                 # takes a few minutes
docker save akademija-test/app:$SHA | gzip > akademija-test-app-$SHA.tar.gz
sha256sum akademija-test-app-$SHA.tar.gz > akademija-test-app-$SHA.tar.gz.sha256
tar -czf akademija-test-files-$SHA.tar.gz -C /tmp/akademija-test-$SHA \
  docker-compose.test-server.yml .env.test-server.example docker/test-server tools/test-server
```

- `git archive` takes the committed files only — no local `vendor/`, no `.env`, nothing uncommitted.
- The build stops with an error on purpose if Debugbar would be installed or a built JS/CSS bundle is
  missing.
- Result: `akademija-test-app-<SHA>.tar.gz` (the image), its `.sha256`, and
  `akademija-test-files-<SHA>.tar.gz` (compose file, Caddyfile, this README, the env example, the
  preparation script).
- **The database dump** (a read-only copy of the dev database `akademija`) is a separate file you
  already have (it is never in git or in the image); see section 5.

Copy the files to the server (from your computer; on Windows use PowerShell, it has `scp`):

```bash
scp akademija-test-app-<SHA>.tar.gz akademija-test-app-<SHA>.tar.gz.sha256 \
    akademija-test-files-<SHA>.tar.gz root@SERVER_IP:/opt/akademija-test/incoming/
scp <the dump file> root@SERVER_IP:/root/akademija-test-dump.sql      # not inside /opt/akademija-test
```
(create `/opt/akademija-test/incoming` first, section 3).

## 3. On the server: one-time preparation

**a. Look before changing anything** (all read-only):

```bash
uname -m                                  # x86_64 expected (the image is linux/amd64)
free -h                                   # "available" should be well above 2.5 GB (our limits + fpl-live)
docker version --format '{{.Server.Version}}' && docker compose version     # Compose v2 ("v2.x")
docker ps --format '{{.ID}} {{.Names}} {{.Image}} {{.CreatedAt}} {{.Ports}}' | tee /root/docker-ps-before.txt
                                          # all running containers (fpl-live's among them), kept for later
                                          # (CreatedAt, not Status: "Up 2 hours" changes by itself)
ss -ltnp | grep -E ':8080\b' || echo "port 8080 is free"                     # must say free
ufw status verbose                        # current firewall rules (just look)
```

**b. Folders, image, settings:**

```bash
mkdir -p /opt/akademija-test/incoming && cd /opt/akademija-test        # never /opt/fpl-live
(cd incoming && sha256sum -c akademija-test-app-<SHA>.tar.gz.sha256)   # must print "…tar.gz: OK"
gunzip -c incoming/akademija-test-app-<SHA>.tar.gz | docker load      # "Loaded image: akademija-test/app:<SHA>"
tar --no-same-owner -xzf incoming/akademija-test-files-<SHA>.tar.gz -C /opt/akademija-test   # files owned by root
cp -n .env.test-server.example .env.test-server && chmod 600 .env.test-server
```

Make the Basic Auth hash (the password is typed twice and not shown):

```bash
docker run --rm -it caddy:2.8.4-alpine caddy hash-password             # prints a line starting with $2a$
```

Edit `.env.test-server` (`nano .env.test-server`) and fill in every `<...>`:
`APP_IMAGE_TAG=<SHA>`, `BASIC_AUTH_USER`, `BASIC_AUTH_HASH='<the $2a$… line>'` (keep the single
quotes), `APP_KEY` (make one with `echo "base64:$(openssl rand -base64 32)"`),
`APP_URL=http://SERVER_IP:8080` (the real IP), `DB_PASSWORD`, `DB_ROOT_PASSWORD` (two different random
passwords, e.g. `openssl rand -base64 24`, in single quotes), `MAIL_FROM_ADDRESS`.

**c. The `dc` helper** — every Docker command in this guide goes through it, so it always targets this
project and never `fpl-live`. Define it in every new SSH session (or add it to `/root/.bashrc`):

```bash
dc() { docker compose --project-name akademija-test \
  --file /opt/akademija-test/docker-compose.test-server.yml \
  --env-file /opt/akademija-test/.env.test-server "$@"; }
dc config --quiet && echo "config OK"                                  # checks the file and the settings
dc config | grep -E '^  [a-z]+:$|published:|host_ip:'
      # lists app:, db:, proxy: and then ONE "published: \"8080\"" — directly under proxy:, nothing
      # under app: or db: (the lines after proxy: are networks / volumes / configs, not services)
```

**d. Firewall (ufw) — and why it is not what protects this port.** Docker publishes container ports by
adding its own firewall (iptables) rules, which are applied **before** ufw. So as soon as the proxy runs,
port 8080 is reachable from the internet **even if ufw does not allow it**, and `ufw deny 8080` would
not close it. The real protection is: only the proxy publishes a port, and it asks for the Basic Auth
password. Add the ufw rule anyway, so the firewall rules document what is open:

```bash
ufw allow 8080/tcp comment 'akademija-test (Docker publishes it regardless of ufw)'
ufw status numbered                        # shows the new rule; nothing else changed
```
To close the site to everyone: `dc stop proxy` (section 8). Limiting 8080 to a few IP addresses would
need rules in Docker's `DOCKER-USER` chain, which affect every container on the server — not done here;
ask before changing it.

## 4. First start (in this order)

| # | Command | What it does / what to expect |
|---|---|---|
| 1 | `dc up -d db` | starts MariaDB; on the empty volume it creates the database `akademija_test` and its user. `Container akademija-test-db-1 Started` |
| 2 | `dc ps db` (repeat until it says `healthy`, ~30 s) | the database is ready |
| 3 | **import the dump — section 5** | |
| 4 | `dc up -d --no-build app` | starts the app from the loaded image (`--no-build`: never build on the server) |
| 5 | **prepare the copy — section 6** (before anyone else gets the address) | |
| 6 | `dc exec -u www-data app php artisan optimize:clear` | empties the cached config / routes / views / events (`DONE` lines) |
| 7 | `dc exec -u www-data app php artisan cache:clear` | **mandatory** (model cache keys of this version; also after every update or rollback) |
| 8 | `dc exec -u www-data app php artisan permission:cache-reset` | no output and no error (it may print `Permission cache flushed.`) |
| 9 | `dc exec -u www-data app php artisan view:clear` | then warm up the first pages, one at a time: |
| 10 | `for u in / /sr/login /en/login /sr-latn/login /en/password/reset /sitemap.xml; do dc exec -T app curl -s -o /dev/null -w "$u %{http_code}\n" http://127.0.0.1$u; done` | each line ends in `200` (from inside the app container, so no password is needed) |
| 11 | `dc up -d proxy` | starts the proxy: from now on `http://SERVER_IP:8080` is reachable (with the Basic Auth password) |
| 12 | **checks — section 7**, then give the address, the Basic Auth user/password and each tester's own login to the testers | |

## 5. Import the database copy (only into this project's database)

The dump is a **read-only copy of the dev database `akademija`** (same data as dev, users and files rows
included, not anonymised). It goes **only** into `akademija_test` in this project's own MariaDB
container, which holds no other database. **Never run migrations** afterwards (5 migrations look
pending although their tables exist).

```bash
cd /opt/akademija-test
grep -nE '^(CREATE DATABASE|USE |DROP DATABASE)' /root/akademija-test-dump.sql || echo "no database statements: OK"
dc exec db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -N -e "SHOW DATABASES; SELECT @@version"'
      # expect: akademija_test, information_schema, mysql, performance_schema, sys, and 10.11.19-MariaDB...
dc exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < /root/akademija-test-dump.sql
      # silent for a minute or two; no output = no error
dc exec db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE(); SELECT COUNT(*) FROM typicms_users; SELECT COUNT(*) FROM typicms_pages; SELECT COUNT(*) FROM typicms_files;"'
      # four numbers; for backups/akademija-dev-2026-11-04.sql: 33, 5, 109, 7500
```
The dump starts with a `/*M!999999\- enable the sandbox mode */` line: that is normal for a
MariaDB 10.11.19 dump (the import above uses the same version).

Re-import later (e.g. to reset the copy): empty **only this database**, import as above, remove the
"already prepared" marker of section 6, and prepare it again:
```bash
dc exec db sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "DROP DATABASE \`$MARIADB_DATABASE\`; CREATE DATABASE \`$MARIADB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL ON \`$MARIADB_DATABASE\`.* TO \`$MARIADB_USER\`@\`%\`;"'
# … import (above) …
dc exec -u www-data app rm -f storage/app/test-copy-prepared.json     # only after a fresh import
```

## 6. Prepare the copy (after the import, before anyone else opens the site)

Changes **only this copy** (never dev, never production), in one transaction:
- **a.** adds one new **test superadmin** — you choose the e-mail and type its password (asked twice,
  not shown, not in git, not in any log);
- **b.** gives **every other user** a different strong random **test password** (20 characters); only
  its hash is stored in the database. The list (e-mail, test password) goes **only** into
  `/root/akademija-test-credentials-<date>.tsv`, mode 600 (root only) — outside the app folder, the
  image, git and every Docker volume. Nothing with a password is printed to the terminal or logged;
- **c.** invalidates login tokens: `remember_token` emptied and a new random `api_token` for every
  existing user (that column cannot be empty), and the password reset table emptied. (No other token
  tables exist; sessions are files and start empty on this server);
- **d.** checks that the only new row is the superadmin and that, for every existing user, only
  `password`, `remember_token` and `api_token` changed; after committing, that **no other table**
  changed (checksums before / after). If a check fails, nothing is changed (rolled back).

```bash
bash /opt/akademija-test/tools/test-server/prepare-test-copy.sh <superadmin e-mail> <first name> <last name>
dc exec -u www-data app php artisan cache:clear
```
Expected output (after the two password prompts):
```
Prepared the test copy "akademija_test":
  new test superadmin: id 6, <e-mail> (activated, superuser, e-mail verified)
  existing users: 5 — new test password (hash stored), remember_token emptied, new api_token; all other columns unchanged (checked)
  password reset rows removed: 2
  credentials written to STDOUT: 5 lines (email, test password)
  other tables: 31 checked with CHECKSUM TABLE, none changed
Credentials of 5 existing users written to /root/akademija-test-credentials-<date-time>.tsv (mode 600, root only).
```
(the numbers are those of the 2026-11-04 dump: the new id is the table's next number, 6; a run that
was refused or rolled back can use up a number, so it may be higher — that is harmless.)

**It runs once per import.** Run again, it stops before changing anything — *"this copy was
already prepared (date, superadmin …). Nothing changed."* — and writes no credentials file; an
existing credentials file is never overwritten. If a check fails, everything is rolled back and no
credentials file is written. To prepare again, re-import first (section 5).

Existing users log in with their test passwords, which you give each of them individually. The test
superadmin can also create more test users in the admin (Users → New). Their e-mail address must
have a real mail domain: the form checks it with a DNS lookup (`email:rfc,dns`), so e.g.
`…@example.invalid` is rejected; no mail is sent to it (`MAIL_MAILER=log`).

**The credentials file** — copy it to your computer (run this **on your computer**):
```bash
scp root@SERVER_IP:/root/akademija-test-credentials-<date>.tsv .
```
Remove it from the server **only when you decide to** (after you have passed the accounts on):
```bash
shred -u /root/akademija-test-credentials-<date>.tsv
```

## 7. Checks after starting

**a. Isolation** (on the server):
```bash
ss -ltnp | grep -E ':8080\b'                      # docker-proxy on 0.0.0.0:8080 (and [::]:8080) — the only new port
                                                  # (with PROXY_BIND set, the address shown there)
docker port akademija-test-proxy-1                # 8080/tcp -> 0.0.0.0:8080
docker port akademija-test-app-1; docker port akademija-test-db-1    # both print nothing
docker ps --format '{{.ID}} {{.Names}} {{.Image}} {{.CreatedAt}} {{.Ports}}' > /root/docker-ps-after.txt
diff /root/docker-ps-before.txt /root/docker-ps-after.txt   # only "> …akademija-test-…" lines (the three new ones);
                                                            # no "<" line: fpl-live's containers are the same ones
docker ps --filter status=running --format '{{.Names}} {{.Status}}'   # fpl-live's containers still "Up"
dc exec -u www-data app php artisan about --only=environment    # Environment staging, Debug Mode OFF, URL SERVER_IP:8080
dc exec proxy sh -c 'printenv BASIC_AUTH_HASH | cut -c1-7'      # $2a$14$  (the hash arrived unchanged;
                                                                # "dc config" shows it as $$2a$$… — that is only its display)
```

**b. From outside** (on your computer, ideally on another network, e.g. a phone hotspot):
```bash
curl -sI http://SERVER_IP:8080/ | head -3                         # HTTP/1.1 401 Unauthorized + WWW-Authenticate: Basic
curl -s -o /dev/null -w '%{http_code}\n' -u '<user>:<password>' http://SERVER_IP:8080/    # 200
curl -s -u '<user>:<password>' -D - -o /dev/null http://SERVER_IP:8080/sr/login | grep -iE '^(set-cookie|x-robots-tag)'
    # cookies WITHOUT "secure" (plain HTTP must work), "x-robots-tag: noindex, nofollow, noarchive"
curl -s -u '<user>:<password>' -D - -o /dev/null http://SERVER_IP:8080/admin | grep -i '^location'
    # Location: http://SERVER_IP:8080/sr/login   (redirects keep the address and port)
nc -zv -w 3 SERVER_IP 3306                               # refused / timed out (the database publishes no port)
F='Authorization: Bearer 00000000-0000-4000-8000-000000000000'          # a fake token
curl -s -o /dev/null -w '%{http_code}\n' -H "$F" -H 'Accept: application/json' http://SERVER_IP:8080/api/users   # 401 (from the app: token refused)
curl -s -o /dev/null -w '%{http_code}\n' -H "$F" http://SERVER_IP:8080/admin                                      # 401 (Basic Auth: not an exception)
curl -s -o /dev/null -w '%{http_code}\n' -H "$F" http://SERVER_IP:8080/API/news                                   # 401 (the exception is case-sensitive)
curl -s --path-as-is -o /dev/null -w '%{http_code}\n' -H "$F" 'http://SERVER_IP:8080/api/../admin'               # 401 (no way around it with ../)
```
Then in a browser: `http://SERVER_IP:8080` → the Basic Auth prompt → the site; log in at `/sr/login`
with the test superadmin; the admin opens; an admin list (e.g. News) shows its rows; upload an image
in Files → its thumbnail shows (Croppa on the local disk); log out and in again (the session cookie
works over HTTP).

**c. Debugbar is not active** (UPGRADE_PLAN O5):
```bash
dc exec app sh -c 'test ! -d vendor/barryvdh/laravel-debugbar && echo "debugbar not installed"'
curl -s -u '<user>:<password>' http://SERVER_IP:8080/ | grep -cE 'Laravel Debugbar Widget|phpdebugbar'   # 0
curl -s -o /dev/null -w '%{http_code}\n' -u '<user>:<password>' http://SERVER_IP:8080/_debugbar/open      # 404
```
and in the browser, logged in: the page source of an admin page contains neither string.

Why this works over plain HTTP behind the proxy without trusting it: the proxy passes the original
`Host` (`SERVER_IP:8080`) to the app, both sides use HTTP, `SESSION_SECURE_COOKIE` is unset (cookies
are not Secure-only), and Croppa on the local disk returns the image directly. The app does not trust
the proxy's `X-Forwarded-*` headers (`config/trustedproxy.php`: none); it only means the app sees the
proxy's IP as the visitor's IP (the login throttle still works per e-mail).

**The admin-API limit — what testers may see.** The application allows **60 admin-API requests per
minute in total**, for all users and all IP addresses together (`RateLimiter::for('api')` →
`Limit::perMinute(60)` with no per-user key; measured in the rehearsal: two users alternating got
30 + 30, then `429` with `Retry-After` of about a minute). This is the same on production (same code;
Laravel 8 builds the key the same way) and does not depend on the proxy. Opening an admin list, the
file manager or the file picker costs one request each; a status or position change one more. A few
testers working normally stay well below it; many fast clicks by several people at once (or the O9
captures, ~26 requests each) can reach it: the list then shows an error message instead of rows, and
after about a minute it works again (reload the page).

## 8. Everyday operation

| Task | Command | Notes |
|---|---|---|
| status | `dc ps` | three containers `Up`, db `healthy` |
| proxy / web server log | `dc logs --tail=100 proxy`, `dc logs --tail=100 app` | add `-f` to follow, Ctrl+C to stop following. Apache's access and error log are only here (in the image `/var/log/apache2/*.log` point to the container output, so `tail` on them never ends); the client IP there is the proxy's for every visitor |
| application log (errors, and the mails of `MAIL_MAILER=log`) | `dc exec -u www-data app tail -n 100 storage/logs/laravel.log` | |
| database log | `dc logs --tail=100 db` | |
| restart one service | `dc restart app` (or `proxy`, `db`) | does **not** re-read `.env.test-server`; after changing it use `dc up -d --no-build app` (recreates the container, the volumes stay) |
| close the site for everyone | `dc stop proxy` | `dc up -d proxy` opens it again |
| the Caddyfile was changed (new files bundle) | `dc up -d --no-deps --force-recreate proxy` | the Caddyfile is mounted as one file: a new copy is only seen by a new proxy container (`dc up -d proxy` alone keeps the old one) |
| stop everything of this project | `dc stop` | data stays; `dc up -d --no-build` starts it again |
| `dc up` was interrupted (error, timeout) and `dc ps -a` shows the app as `Created`, or with a name like `1a2b3c_akademija-test-app-1` | run the same `dc up -d --no-build app` again; if the odd name stays: `dc up -d --no-build --force-recreate app` | recreates only the app container; the database and the storage volume (uploads, sessions) stay (seen in the local rehearsal) |
| admin lists briefly show an error, `429 Too Many Requests` in `dc logs app` | wait a minute | existing application behaviour: **60 admin-API requests per minute for all users together** (`RateLimiter::for('api')` has no per-user key); several testers clicking through lists at the same time can reach it |

**Update to a new version:** on your computer section 2 (new `<SHA>`); on the server: `scp`, then
`sha256sum -c`, `docker load` (section 3b), `tar --no-same-owner -xzf` the new files bundle, set
`APP_IMAGE_TAG=<new SHA>` in `.env.test-server`, then `dc up -d --no-build app` and steps 6–10 of
section 4 (`cache:clear` is mandatory). If the files bundle has a new Caddyfile, also
`dc up -d --no-deps --force-recreate proxy`. The database and the uploads stay.

**Roll back to the previous version:** the previous image is still loaded (`docker images
akademija-test/app` lists the tags). Set `APP_IMAGE_TAG=<previous SHA>`, `dc up -d --no-build app`,
then steps 6–10 of section 4 — `cache:clear` is mandatory here too (a cache written by a newer version
can break an older one).

## 9. NEVER run these on this server

| Command | Why not |
|---|---|
| `docker system prune`, `docker image prune -a`, `docker volume prune`, `docker network prune`, `docker container prune` | they act on **all** projects: they can delete `fpl-live`'s images, stopped containers, networks or **data volumes** |
| `dc down -v` / `docker compose down -v` / `docker volume rm` | `-v` deletes this project's volumes: **the database copy and all uploads are gone** |
| any `docker compose …` **without** `--project-name akademija-test` (use `dc`), or run in `/opt/fpl-live` | could stop, recreate or remove `fpl-live` |
| `docker stop $(docker ps -q)`, `docker rm -f $(docker ps -aq)`, `systemctl restart docker` | stop / remove **every** container on the server, `fpl-live` included |
| `php artisan migrate` (and `migrate:*`, `db:wipe`, `db:seed`) | 5 migrations look pending although their tables exist with data: they would fail or change the schema |
| `php artisan route:cache`, `view:cache` | they fail on this application (UPGRADE_PLAN §16); `config:cache` is not needed either |
| `php artisan …` **without** `-u www-data` (as root) | creates root-owned files in `storage/` that the web server cannot write → 500 errors (UPGRADE_PLAN R24) |
| publishing a port for `app` or `db` (editing `ports:` in the compose file) | would put the app without Basic Auth, or the database, on the internet (Docker bypasses ufw) |
| `ufw disable`, `ufw reset`, changing Docker's `DOCKER-USER` / iptables rules | affect the whole server, `fpl-live` included |
| `MAIL_MAILER=mailgun` with real credentials | the contact form sends to a real address (`info@au.unibl.org`) |
| copying production keys, the production `.env` or a production dump to this server | this server is not production; production is only ever read, from your computer |

## 10. Remove the test data at the end of the testing period (only when you decide)

```bash
dc down                                              # containers and network of this project; data stays
docker volume rm akademija-test_db_data akademija-test_storage   # the copy (real user data) and the uploads
shred -u /root/akademija-test-dump.sql /root/akademija-test-credentials-*.tsv
```

## 11. Later: switch the files to the non-production S3 test bucket

1. **The bucket:** a separate non-production bucket and an IAM user that can access **only** that bucket,
   with the production bucket's settings (Object Ownership / ACLs, Block Public Access — UPGRADE_PLAN
   O2(b)).
2. **One-way copy of the files** (optional, so existing images display), run **by you on your
   computer**, never on the test server: read from production, write to the test bucket. The production
   keys stay on your computer and are only used to read; the copy never goes the other way:
   ```bash
   aws s3 sync s3://<production bucket>/files/    s3://<test bucket>/files/    --acl public-read --profile <read-prod-write-test>
   aws s3 sync s3://<production bucket>/settings/ s3://<test bucket>/settings/ --acl public-read --profile <read-prod-write-test>
   ```
   (no `--delete`; check the prefixes in the production bucket first, read-only:
   `aws s3 ls s3://<production bucket>/ --profile …`; whether `--acl public-read` is accepted depends on
   the test bucket's Object Ownership setting.)
3. **On the server, in `.env.test-server`:** `FILESYSTEM_DRIVER=s3`, and uncomment and fill
   `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` (the **test** user's keys), `AWS_DEFAULT_REGION`,
   `AWS_BUCKET` (the test bucket); `AWS_URL` / `AWS_ENDPOINT` only if production uses them. **The image
   is not rebuilt.**
4. `dc up -d --no-build app`, then steps 6–10 of section 4.
5. Files uploaded during the local-disk phase stay in the storage volume and are not in the bucket.
6. **Repeat all file-related checks** on the bucket: UPGRADE_PLAN §18.4 C and the file parts of A
   (Files uploads, News "Add files", images of sections and modules). Only then can O1 / O2(c) / O3
   move.

## 12. Later: a subdomain instead of `SERVER_IP:8080`

Nothing changes in `fpl-live` until then. When the subdomain exists (DNS record pointing to the server):
- **fpl-live's Caddy** (owned by fpl-live; changed by whoever maintains it, then *reloaded*, not
  restarted) gets one more site block, e.g. `akademija-test.example.org { reverse_proxy 127.0.0.1:8080 }`
  — HTTPS certificates are then handled there. Basic Auth can stay in our proxy.
- **`.env.test-server`:** `PROXY_BIND=127.0.0.1:8080` (the proxy is then reachable only through
  fpl-live's Caddy; `dc up -d proxy` applies it), and the ufw rule for 8080 is removed.
- **`.env.test-server`:** `APP_URL=https://akademija-test.example.org`, `SESSION_SECURE_COOKIE=true`.
- **Open question for then:** behind an HTTPS proxy the app sees plain HTTP; to build `https://` links
  it must trust the proxy's `X-Forwarded-Proto`. `config/trustedproxy.php` trusts no proxy today and is
  not read from the environment, so this needs a decision about an application config change then
  (CLAUDE.md: report first).

---

## Deploy checklist

- [ ] Section 2 on your computer: clean `deploy/test-server`, image built from `git archive`, `.sha256` made
- [ ] Section 3a: `docker ps` saved to `/root/docker-ps-before.txt`, port 8080 free, enough memory
- [ ] Section 3b: `sha256sum -c` OK, image loaded, `.env.test-server` filled (no `<...>` left), mode 600
- [ ] Section 3c: `dc config` OK, exactly one published port (proxy 8080)
- [ ] Section 3d: ufw rule added
- [ ] Section 4 steps 1–4: db healthy, dump imported (counts match), app started
- [ ] Section 6: copy prepared ("none changed"), credentials file exists (mode 600), `cache:clear`
- [ ] Section 4 steps 6–11: caches cleared as www-data, pages warmed (all 200), proxy started
- [ ] Section 7: isolation (`diff` shows only akademija-test), outside checks (401 / 200 / no Secure
      cookie / redirect keeps the address / no DB port), Debugbar absent, browser login + upload
- [ ] O9 cold rounds (below)
- [ ] UPGRADE_PLAN §18.4 manual checks (below)

## O9 — cold compiled views (UPGRADE_PLAN O9)

Read-only GET requests through the golden-master tool, inside the app container as www-data (no Basic
Auth inside). The captures contain data of the copy and are written to `storage/golden-master/` in the
storage volume; delete them at the end.

```bash
TOKEN=$(dc exec -T db sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" -N -e "SELECT api_token FROM typicms_users WHERE superuser=1 ORDER BY id LIMIT 1"')
cap() { dc exec -T -u www-data -e GM_API_TOKEN="$TOKEN" app php tools/golden-master/capture.php "$@" --base=http://127.0.0.1:80; }
dc exec -u www-data app php artisan view:clear
cap o9-warmup > /dev/null 2>&1; dc exec -u www-data app rm -rf storage/golden-master/o9-warmup   # warm-up, discarded
sleep 65; cap o9-warm                                                                       # warm baseline
# sleep 65 before each capture: one capture sends ~26 admin-API requests, the API allows 60 per minute
# for all users together; without the pause a fast round gets "429" answers (not an O9 finding)
for i in 1 2 3; do sleep 65; dc exec -u www-data app php artisan view:clear; cap o9-cold$i --parallel=6; \
  dc exec -T -u www-data app php tools/golden-master/compare.php o9-warm o9-cold$i | head -12; done
for i in 4 5; do sleep 65; dc exec -u www-data app php artisan view:clear; cap o9-cold$i --parallel=12; \
  dc exec -T -u www-data app php tools/golden-master/compare.php o9-warm o9-cold$i | head -12; done
dc exec -T -u www-data app sh -c 'for d in storage/golden-master/o9-*; do n=$(awk -F"\t" "\$6==\"/sr/login\"{print \$1}" $d/index.tsv); \
  printf "%s /sr/login logo: %s\n" "$d" "$(grep -c "class=\"logo\"" $d/bodies/$(printf %04d $n).txt)"; done'
dc exec -u www-data app rm -rf storage/golden-master                                       # afterwards
```
Expected: `same: 429` in every round and `logo: 1` for every capture. Any 500 (`File does not exist at
path …/views/…`), `logo: 0`, or any other difference is a finding — record it in UPGRADE_PLAN O9. In the
local rehearsal a capture took about 25 s (the whole check about 10 minutes with the pauses). Run the
rounds before the testers start (they add load and share the API limit); the token is that of the
first existing superadmin. Local rehearsal result (2026-11-04, Docker volume on a Linux VM): 5 of 5
rounds 429 / 429 identical, logo on /sr/login in every capture.

## Manual checks — UPGRADE_PLAN.md §18.4 (yours, in the browser)

The full steps are in UPGRADE_PLAN.md §18.4; in short:
- **A. Saving in the admin**, for every module: list; create in sr / sr-latn / en (shows in the list and
  on the public page); edit one language only; validation error saves nothing; history entries; delete.
  Module-specific: **Pages sections** (list, position + refresh, status, add, edit, delete, order on the
  public page); **News files** ("Add files", save, refresh, remove, save, refresh); **Menus**,
  **Class schedules** (modal), **Users** (valid and invalid e-mail, R16), **Settings** (change and
  restore), **Roles**, **Files** (upload image and PDF, edit alt text).
- **B. Contact form** with `MAIL_MAILER=log`: send it on `/akademija/kontakt`, `/sr-latn/akademija/kontakt`,
  `/en/contact` → "Mail sent"; the mail (To `info@au.unibl.org`, From the entered address) is in
  `storage/logs/laravel.log` (section 8); an empty / invalid e-mail gives an error and no mail.
- **C. File-manager thumbnails:** on the local disk only new uploads have files (existing ones are
  missing, see section 1). Repeat C and the file parts of A after the switch to the S3 test bucket
  (section 11).
