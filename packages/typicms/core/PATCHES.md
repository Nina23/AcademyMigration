# Patches against typicms/core 8.0.11

Pinned in-repo copy of `typicms/core` **8.0.11** (dist reference `e866a563e8102a382eccaba6bbffca7220a66594`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.11"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
