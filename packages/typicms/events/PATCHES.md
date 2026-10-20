# Patches against typicms/events 9.0.4

Pinned in-repo copy of `typicms/events` **9.0.4** (dist reference `666c7e7e530ab9d8606415bb1b334c1e79df6354`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "9.0.4"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
