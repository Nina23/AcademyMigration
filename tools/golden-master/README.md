# Golden master (upgrade safety net)

Captures the HTTP output of a fixed list of URLs from the **dev** app
(`http://localhost:8080`, database `akademija`) so it can be diffed after every
upgrade step. It covers areas the test suite does not: real production content,
the sitemap, menus, blocks, search, and the admin API with the exact query
strings the Vue admin sends.

**Read-only.** Only `GET` requests are sent, without cookies. No admin login, no
Croppa thumbnail URLs (they would write crops to disk). The DB is checksummed
before and after a capture to prove nothing was written.
One known file side effect (no DB): TypiCMS `Presenter::imgNotFound()` copies
`public/img/img-not-found.png` to `storage/app/public/` the first time a thumb is
requested for an item without image (API `append=thumb`). Leave it in place: when
it is missing, the parallel requests of a capture race to create it on the
Windows bind mount and some of them fail with 500 (`Permission denied`).

Captured output contains production data (including user emails from
`/api/users`), so it is written to `storage/golden-master/<label>/`, which is
gitignored. Never commit it.

## Files

| File | Purpose |
|---|---|
| `urls.txt` | the fixed URL list. `API ` prefix = sent with `Accept: application/json` and the API bearer token |
| `capture.php` | `php tools/golden-master/capture.php <label>` |
| `compare.php` | `php tools/golden-master/compare.php <labelA> <labelB>` |

Run on the host (PHP ≥ 7.4 with curl), with the Docker stack up.

## Capture

```bash
# token of an existing superuser, read from the dev DB (not printed)
export GM_API_TOKEN=$(docker compose exec -T mariadb mariadb -uakademija -pakademija akademija -N \
  -e "select api_token from typicms_users where id=1")
php tools/golden-master/capture.php before-step-0
```

Options: `--base=http://localhost:8080`, `--parallel=6`.

## Compare

```bash
php tools/golden-master/compare.php before-step-0 after-step-0
```

Before comparing, bodies are normalized:
- the debugbar head and body injections are removed (they differ per request
  and the debugbar package itself is upgraded);
- CSRF token values are masked;
- for `5xx` responses only the status is compared (the error page renderer
  changes with the upgrade: facade/ignition → spatie/laravel-ignition).

The report lists each URL as `same`, `whitespace-only`, or `DIFF`, and writes a
unified diff per changed URL to `storage/golden-master/compare-<A>-vs-<B>/`.

Content that depends on the current date (e.g. announcements filtered by expiry)
can change between captures taken on different days. Re-capture the reference on
the same day when in doubt.
