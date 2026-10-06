# Patches against typicms/blocks 8.0.0

Pinned in-repo copy of `typicms/blocks` **8.0.0** (dist reference `52b1adf7f04fa33b45308df6515a7fe2098b4d57`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.0"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
