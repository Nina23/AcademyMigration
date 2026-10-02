<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\TestDatabaseGuard;
use TypiCMS\Modules\News\Models\News;
use TypiCMS\Modules\Users\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    public const PASSWORD = 'correct-horse-battery-staple';

    protected function setUp(): void
    {
        parent::setUp();

        // Second line of defence (the first is tests/bootstrap.php): every test
        // re-checks that its freshly booted application is on a *_testing DB.
        TestDatabaseGuard::assertSafe($this->app);
    }

    /**
     * An activated, e-mail-verified user (the admin area requires both).
     */
    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'email' => Str::random(12).'@example.test',
            'password' => Hash::make(self::PASSWORD),
            'first_name' => 'Test',
            'last_name' => 'User',
            'activated' => 1,
            'superuser' => 0,
            'email_verified_at' => now(),
        ], $attributes));
    }

    protected function createSuperUser(array $attributes = []): User
    {
        return $this->createUser(array_merge(['superuser' => 1], $attributes));
    }

    /**
     * A regular (non-super) user holding exactly the given Spatie permissions.
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        $user = $this->createUser();
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user->givePermissionTo($permissions);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    /**
     * Headers the Vue admin sends to the API (resources/js/admin.js).
     *
     * The token guard caches the resolved user for the lifetime of the app
     * instance, which a test shares across requests; reset it so every request
     * authenticates from its own token, as it does in production.
     */
    protected function apiHeaders(User $user): array
    {
        // (AuthManager::forgetGuards() does not exist yet in Laravel 8.30.)
        $this->app->forgetInstance('auth');
        $this->app->forgetInstance('auth.driver');
        Facade::clearResolvedInstance('auth');

        return [
            'Authorization' => 'Bearer '.$user->api_token,
            'Accept' => 'application/json',
        ];
    }

    /**
     * A published news item with translations for all three locales.
     */
    protected function createNews(array $attributes = []): News
    {
        return News::create(array_merge([
            'date' => '2026-01-15',
            'category_id' => 1,
            'title' => ['sr' => 'Изложба студената', 'sr-latn' => 'Izložba studenata', 'en' => 'Student exhibition'],
            'slug' => ['sr' => 'izlozba-studenata-cr', 'sr-latn' => 'izlozba-studenata', 'en' => 'student-exhibition'],
            'status' => ['sr' => '1', 'sr-latn' => '1', 'en' => '1'],
            'highlight' => ['sr' => '0', 'sr-latn' => '0', 'en' => '0'],
            'summary' => ['sr' => 'Кратак опис', 'sr-latn' => 'Kratak opis', 'en' => 'Short summary'],
            'body' => ['sr' => '<p>Тијело</p>', 'sr-latn' => '<p>Tijelo</p>', 'en' => '<p>Body</p>'],
        ], $attributes));
    }
}
