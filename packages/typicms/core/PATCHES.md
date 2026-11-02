# Patches against typicms/core 8.0.11

Pinned in-repo copy of `typicms/core` **8.0.11** (dist reference `e866a563e8102a382eccaba6bbffca7220a66594`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.11"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
| 4 (L12) | `src/Models/Base.php` | `uri()`: when the translated slug is `''`, return `url(str_replace('{slug}', '', <route uri>))` instead of calling `route()` | Laravel 12 `route()` rejects an empty parameter (`UrlGenerationException`); Laravel 8–11 replaced `{slug}` with `''` and the URL ended at the module page (`…/en/news`). The language switcher calls `uri($locale)` for every locale, so every item not translated into a locale made its page 500. Same URL as before (checked for 4,488 model × locale combinations on the L12 trial). Pinned by `tests/Feature/Models/UriEmptySlugTest.php` |
| 4 (L12) | `src/Services/Dates.php`, `src/Presenters/Presenter.php` | new `Dates::formatLocalized(DateTimeInterface $date, string $format)` = Carbon 2.73's `formatLocalized()` (Windows `%e` branch, `strftime($format, strtotime(<Y-m-d H:i:s>))`, strict = no `@`); `Presenter::dateLocalized()` / `dateTimeLocalized()` and `Dates::concat()` call it instead of `$carbon->formatLocalized()` | Carbon 3 (required by Laravel 12) removed `formatLocalized()`; the three methods would throw `UnknownMethodException`. Same output as Carbon 2 (C locale, e.g. `" 5 October 2026"`). No module view reaches them today (the modules that render `dateLocalized` override it with `format('d.m.Y')`). Pinned by `tests/Feature/Presenters/LocalizedDatesTest.php` |
