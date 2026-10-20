# Patches against typicms/menus 8.0.2

Pinned in-repo copy of `typicms/menus` **8.0.2** (dist reference `91dfacde22dec5a55c34a8d1cc9dce7c5ffdc213`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.2"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
