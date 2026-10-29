# Akademija — Laravel upgrade rules

## Forbidden
- NEVER run `php artisan migrate`. 5 migrations show as Pending, but their tables already exist with data (the production dump never recorded them in `typicms_migrations`).
- Don't touch the frontend: Blade views, Vue components (`.vue` and all JS), CSS/SCSS, images or other assets — unless explicitly approved.
- Don't change `package.json`, `package-lock.json` or the build setup (`webpack.mix.js`), and don't run `npm install`/`npm update` — unless explicitly approved.
- Don't change the look of anything, on the public site or in the admin panel.
- Don't change how the application works, including modules and Vue components: what they show, what they save, how they behave. The upgrade must leave the app working EXACTLY the same, on a newer version.

## When something has to change
- If the upgrade REQUIRES a change to module, view, Vue or frontend code, STOP first and report: which file, what changes, why it is required, and whether users will notice any difference. Wait for approval.

## Git
- No commit without an explicit "commit".
- No push without an explicit "push".
- Don't start the next step of the plan before an explicit "continue".

## Checks
- Plan per step: `STEP<n>_PLAN.md`; overall plan: `UPGRADE_PLAN.md`.
- After every change, the test suite (`docker compose exec app php vendor/bin/phpunit`, see `tests/README.md`) and the golden master (`tools/golden-master/`) must stay green and identical to the previous step.
