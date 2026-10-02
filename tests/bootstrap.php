<?php

/*
|--------------------------------------------------------------------------
| PHPUnit bootstrap
|--------------------------------------------------------------------------
|
| 1. Verifies we are connected to a "*_testing" database on the production
|    engine/version (MariaDB 10.11.19). Hard stop otherwise.
| 2. Rebuilds that database once per test run from the production schema
|    (tests/Fixtures/schema/akademija_schema.sql), then runs the
|    BaselineSeeder. No production data is ever loaded.
|
| The schema file is extracted from the original production MariaDB dump by
| tests/Support/extract-production-schema.php (INSERTs and AUTO_INCREMENT
| counters removed, all DDL verbatim). It is used instead of the Laravel
| migrations because the migrations cannot build a database from scratch
| (ordering problem) and do not match the production schema.
|
| The rebuild must happen before any test boots the application, because
| TypiCMS reads settings and module pages from the database while booting
| (config('typicms.*') and the front-office module routes).
|
| Set TEST_DB_REBUILD=0 to reuse the existing testing database.
|
*/

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\BaselineSeeder;
use Tests\Support\TestDatabaseGuard;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

TestDatabaseGuard::assertSafe($app);

if (getenv('TEST_DB_REBUILD') !== '0') {
    if ($kernel->call('db:wipe', ['--force' => true]) !== 0) {
        fwrite(STDERR, $kernel->output());

        throw new RuntimeException('db:wipe failed on the testing database.');
    }

    DB::unprepared(file_get_contents(__DIR__.'/Fixtures/schema/akademija_schema.sql'));

    if ($kernel->call('db:seed', ['--class' => BaselineSeeder::class, '--force' => true]) !== 0) {
        fwrite(STDERR, $kernel->output());

        throw new RuntimeException('BaselineSeeder failed on the testing database.');
    }
}

$app->flush();
unset($app, $kernel);
