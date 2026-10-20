# Patches against typicms/search 8.0.0

Pinned in-repo copy of `typicms/search` **8.0.0** (dist reference `f4ac2d6c20504f97fc1bf8696d4475f38289d32a`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.0"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
