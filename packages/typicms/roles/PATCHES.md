# Patches against typicms/roles 8.0.1

Pinned in-repo copy of `typicms/roles` **8.0.1** (dist reference `c664d930e0b305a37c7813630c4806ff8298578d`), copied byte-for-byte from the installed `vendor/` on 2026-10-06 (upgrade step 0). Every change made to this copy since is listed below.

| Step | File | Change | Reason |
|---|---|---|---|
| 0 | `composer.json` | added `"version": "8.0.1"` | path repository needs a fixed version so the root constraints keep resolving to the installed version |
| 0 | `composer.json` | widened the Laravel/Illuminate constraint up to `^13.0` | install only; no code change |
| 1 (L9) | `src/Models/Role.php` | Spatie Permission 6 contract signatures: `findByName(string $name, ?string $guardName = null)`, `findById(int\|string $id, ?string $guardName = null)`, `findOrCreate(string $name, ?string $guardName = null)`, `hasPermissionTo($permission, ?string $guardName = null)`; in `hasPermissionTo()` the permission class is called statically (`$permissionClass::findByName()` / `::findById()`) | fatal "incompatible declaration" and "call on string" with Permission 6 (`getPermissionClass()` now returns a class name). The new `$guardName` argument is accepted but not used, so the default guard is used exactly as before; queries and results unchanged |
