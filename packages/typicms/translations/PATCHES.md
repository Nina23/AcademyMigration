# Patches against typicms/translations 8.0.0

Pinned in-repo copy of `typicms/translations` **8.0.0** (dist reference `7c824a724504f4935774c61d968697c359f5dbd6`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.0"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
| 2 (L10) | `src/Loaders/MixedLoader.php` | `load()`: `$this->loadPath($this->path, …)` → `$this->loadPaths($this->paths, …)` | Laravel 10 `FileLoader` replaced `$path` / `loadPath()` with `$paths` / `loadPaths()`. The loader is still built with the one path `resources/lang`, so `loadPaths([$path])` returns the same array as `loadPath()` did; framework language files stay unloaded, as before |
| 3 (L11) | `src/Translator.php` (new), `src/Providers/TranslationServiceProvider.php` | new `Translator` extends `Illuminate\Translation\Translator` and overrides only `localeForChoice($key, $locale)` with the Laravel 8–10 logic (`$locale ?: $this->locale ?: $this->fallback`); the provider's `register()` (same closure as Laravel's, which is identical in L10 and L11) builds this class | Laravel 11 `localeForChoice()` returns the fallback locale (`en`) when `$key` is not a translation key of the current locale. The search page calls `@choice(__($module), $count)` with the translated string, so every Serbian count would use the English plural rule (`66 страница` → `66 странице`). Pinned by `tests/Feature/Translations/ChoiceTest.php`. **The override's signature is the L11 one, so this patch only loads on L11+** |
