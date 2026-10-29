# Patches against spatie/laravel-query-builder 6.4.4

Pinned in-repo copy of `spatie/laravel-query-builder` **6.4.4** (dist reference `ab9c4c369fc913d6c020a0f6776f3c82f3e523fb`), copied byte-for-byte from the Composer dist download on 2026-10-22 (upgrade step 2, commit 2c; 70 files, sha256-checked). It replaces the 5.7.0 copy of step 1 (dist reference `ff61a9ea32c5b55cbe63de8e3c6e35a201e6b746`, commit 1c-7b). Every change made to this copy is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 2 (L10) | `composer.json` | added `"version": "6.4.4"` | path repository needs a fixed version |
| 1 (L9), re-applied in 2 (L10) | `src/Concerns/AppendsAttributesToResults.php` | **new file**, verbatim copy of the trait from spatie/laravel-query-builder **3.6.3** (`allowedAppends()`, `addAppendsToResults()`, `addAppendsToCursor()`, `ensureAllAppendsExist()`); byte-identical to the step 1 file | Query Builder 5 removed appends. The admin lists request computed columns with `append=` (`thumb`, `department`, `formatted_year`); Query Builder 3 appended every requested attribute (without `allowedAppends()`). Without this the admin lists lose these columns (module behaviour change) |
| 1 (L9), re-applied in 2 (L10) | `src/QueryBuilder.php` | `use AppendsAttributesToResults;` (+ imports) and, in `__call()`, the Query Builder 3.6.3 hooks: append the requested attributes to a returned `Model`, `Collection`, `LengthAwarePaginator`, `Paginator` or `CursorPaginator` | same as above; identical to 3.6.3 `QueryBuilder::__call()`. Applied by hand on 6.4.4; the added lines are identical to the step 1 patch on 5.7.0. In 6.4.4 the `__call()` body is unchanged from 5.7.0, and `QueryBuilderRequest::appends()`, the `append` parameter name and `InvalidAppendQuery::appendsNotAllowed()` still exist |

**Carry forward:** this patch must be re-applied whenever this copy is replaced by a newer Query Builder version.

Pinned by `tests/Feature/Api/AppendsTest.php`.
