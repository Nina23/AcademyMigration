# Patches against spatie/laravel-query-builder 5.7.0

Pinned in-repo copy of `spatie/laravel-query-builder` **5.7.0** (dist reference `ff61a9ea32c5b55cbe63de8e3c6e35a201e6b746`, as locked in step 1c-2), copied byte-for-byte from the installed `vendor/` on 2026-10-21 (upgrade step 1, commit 1c-7b). Every change made to this copy is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 1 (L9) | `composer.json` | added `"version": "5.7.0"` | path repository needs a fixed version |
| 1 (L9) | `src/Concerns/AppendsAttributesToResults.php` | **new file**, verbatim copy of the trait from spatie/laravel-query-builder **3.6.3** (`allowedAppends()`, `addAppendsToResults()`, `addAppendsToCursor()`, `ensureAllAppendsExist()`) | Query Builder 5 removed appends. The admin lists request computed columns with `append=` (`thumb`, `department`, `formatted_year`); Query Builder 3 appended every requested attribute (without `allowedAppends()`). Without this the admin lists lose these columns (module behaviour change) |
| 1 (L9) | `src/QueryBuilder.php` | `use AppendsAttributesToResults;` (+ imports) and, in `__call()`, the Query Builder 3.6.3 hooks: append the requested attributes to a returned `Model`, `Collection`, `LengthAwarePaginator`, `Paginator` or `CursorPaginator` | same as above; identical to 3.6.3 `QueryBuilder::__call()`. `QueryBuilderRequest::appends()`, the `append` parameter name and `InvalidAppendQuery` still exist in 5.7.0 unchanged |

**Carry forward:** this patch must be re-applied to the Query Builder 6.x copy in upgrade step 2 (Laravel 10).

Pinned by `tests/Feature/Api/AppendsTest.php`.
