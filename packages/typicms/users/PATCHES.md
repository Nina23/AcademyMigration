# Patches against typicms/users 8.0.6

Pinned in-repo copy of `typicms/users` **8.0.6** (dist reference `50acad7ef381088b8970be3a6934d1c3572795f5`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.6"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
| 1 (L9) | `src/Models/User.php` | override `getDefaultGuardName()`: returns the first guard of the model (`Guard::getNames($this)->first()`, falling back to `auth.defaults.guard`) | the default guard of spatie/laravel-permission 6 differs from Permission 3: v6 uses the guard of the current request when the model allows it (`api` on the admin API, `auth:api`), v3 always used the model's first guard (`web`). All permissions and roles use guard `web`, so without this every non-superuser would get 403 on the admin API (`NewsApiTest::test_api_enforces_permissions`). `getGuardNames()` stays `web`, `api` as before; no query or data change |
