<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke tests for the test infrastructure itself: the application boots
 * against the isolated testing database (MariaDB 10.11.19, like production),
 * which is built from the schema extracted from the original production dump
 * and seeded with BaselineSeeder only (no production data).
 */
class TestEnvironmentTest extends TestCase
{
    public function test_application_boots_in_testing_environment_against_testing_database()
    {
        $this->assertTrue($this->app->isBooted());
        $this->assertSame('testing', $this->app->environment());
        $this->assertSame('akademija_testing', DB::connection()->getDatabaseName());
        $this->assertSame('akademija_testing', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertStringStartsWith('10.11.19-MariaDB', DB::selectOne('SELECT VERSION() AS v')->v);
    }

    public function test_database_schema_matches_production_schema()
    {
        $snapshot = file_get_contents(base_path('tests/Fixtures/schema/akademija_schema.sql'));
        preg_match_all('/^CREATE TABLE `([^`]+)`/m', $snapshot, $matches);
        $expected = collect($matches[1])->sort()->values()->all();

        $actual = collect(DB::select(
            'SELECT table_name AS name FROM information_schema.tables WHERE table_schema = DATABASE()'
        ))->pluck('name')->sort()->values()->all();

        $this->assertCount(33, $expected);
        $this->assertSame($expected, $actual);
    }

    public function test_baseline_settings_are_loaded_into_config_during_boot()
    {
        $this->assertSame(['sr', 'sr-latn', 'en'], locales());
        $this->assertSame('1', config('typicms.sr.status'));
        $this->assertSame('1', config('typicms.sr-latn.status'));
        $this->assertSame('1', config('typicms.en.status'));
        $this->assertSame('en', config('typicms.admin_locale'));
    }
}
