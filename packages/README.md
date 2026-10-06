# Pinned package copies

Exact copies of the installed versions of all `typicms/*` packages and of
`spatie/laravel-translatable` 4.6.0, loaded through a Composer `path`
repository (see `composer.json`). Package names and namespaces are unchanged.

Why: every newer TypiCMS major (and Spatie Translatable 5/6) changes behaviour,
the database schema and the admin API, so the upgrade keeps this exact code and
applies only the small compatibility patches it technically needs.

Rules:
- do not "improve" copied code; change it only when the upgrade requires it;
- record every change in the package's `PATCHES.md`.
