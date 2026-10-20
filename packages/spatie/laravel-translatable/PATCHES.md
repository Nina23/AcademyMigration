# Patches against spatie/laravel-translatable 4.6.0

Pinned in-repo copy of `spatie/laravel-translatable` **4.6.0** (dist reference `5900d6002a5795712058a04c7ca7c2c44b3e0850`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "4.6.0"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
