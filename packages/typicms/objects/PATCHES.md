# Patches against typicms/objects 8.0.4

Pinned in-repo copy of `typicms/objects` **8.0.4** (dist reference `7161344f48aa9db2b6e8a140dd7d9433f90a93fa`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.4"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
