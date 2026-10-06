# Patches against typicms/settings 8.0.0

Pinned in-repo copy of `typicms/settings` **8.0.0** (dist reference `44a34e276026c3ba7bc064bad2365e0fd6f669cb`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.0"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
