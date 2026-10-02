<?php

namespace Tests\Feature\Boot;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;
use TypiCMS\Modules\Core\Facades\TypiCMS;

/**
 * Detects container/provider/autoload/config breakage after framework or
 * package upgrades, before any HTTP-level test runs.
 */
class ApplicationBootTest extends TestCase
{
    public function test_every_configured_service_provider_exists_and_non_deferred_ones_are_loaded()
    {
        $loaded = $this->app->getLoadedProviders();

        foreach (config('app.providers') as $provider) {
            $this->assertTrue(class_exists($provider), "Provider class {$provider} does not exist.");

            if (!(new $provider($this->app))->isDeferred()) {
                $this->assertArrayHasKey($provider, $loaded, "Provider {$provider} was not loaded.");
            }
        }
    }

    public function test_all_local_module_models_autoload()
    {
        $models = glob(base_path('Modules/*/Models/*.php'));
        $this->assertNotEmpty($models);

        foreach ($models as $path) {
            $module = basename(dirname($path, 2));
            $class = 'TypiCMS\\Modules\\'.$module.'\\Models\\'.basename($path, '.php');
            $this->assertTrue(class_exists($class), "Model {$class} does not autoload.");
        }
    }

    public function test_core_services_resolve_from_the_container()
    {
        $this->assertSame('sr', TypiCMS::mainLocale());
        $this->assertSame(['sr', 'sr-latn', 'en'], TypiCMS::enabledLocales());
        $this->assertSame('sr', config('app.locale'));
        $this->assertSame('en', config('app.fallback_locale'));

        // DB-backed page registry used to build module routes.
        $this->assertSame(2, TypiCMS::getPageLinkedToModule('news')->id);

        foreach (['translator', 'Settings', 'filesystem.default.driver', 'Bkwld\Croppa\Helpers', 'feed', 'excel'] as $abstract) {
            $this->assertNotNull($this->app->make($abstract), "Could not resolve [{$abstract}].");
        }
    }

    public function test_critical_named_routes_are_registered()
    {
        $routes = [
            // Auth (per locale)
            'sr::login', 'sr-latn::login', 'en::login', 'sr::logout',
            // Admin
            'dashboard', 'admin::index-news', 'admin::create-news', 'admin::store-news', 'admin::update-news',
            'admin::index-pages', 'admin::index-files', 'admin::index-classschedules', 'admin::store-class-schedule-item',
            // DB-driven public module routes
            'sr::index-news', 'sr::news', 'sr-latn::news', 'en::news', 'en::news-feed',
            'sr::index-classschedules', 'sr-latn::index-classschedules',
        ];

        foreach ($routes as $name) {
            $this->assertTrue(Route::has($name), "Route [{$name}] is not registered.");
        }

        // The class schedule page is unpublished in English, so no English route (as in production).
        $this->assertFalse(Route::has('en::index-classschedules'));
    }

    public function test_database_connection_uses_expected_driver_and_charset()
    {
        $config = DB::connection()->getConfig();

        $this->assertSame('mysql', $config['driver']);
        $this->assertSame('typicms_', $config['prefix']);
        $this->assertSame('utf8mb4', $config['charset']);
        $this->assertTrue(Str::startsWith(DB::selectOne('SELECT VERSION() AS v')->v, '10.11.19-MariaDB'));
    }
}
