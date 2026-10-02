<?php

namespace Tests\Support;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

/**
 * Refuses to let the test suite touch any database that is not a dedicated
 * "*_testing" database. Checked both against the configuration and against
 * the database the server actually reports (SELECT DATABASE()).
 *
 * Also requires the server to be the same engine/version as production,
 * so tests never silently run on a different database server.
 */
class TestDatabaseGuard
{
    public const REQUIRED_SUFFIX = '_testing';

    public const REQUIRED_SERVER_VERSION = '10.11.19-MariaDB';

    public static function assertSafe(Application $app): void
    {
        if (!$app->environment('testing')) {
            self::abort('APP_ENV is "'.$app->environment().'", expected "testing".');
        }

        $connection = $app['db']->connection();
        $configured = (string) $connection->getDatabaseName();
        if (!self::isTestingName($configured)) {
            self::abort('Configured database is "'.$configured.'".');
        }

        $actual = (string) $connection->selectOne('SELECT DATABASE() AS db')->db;
        if ($actual !== $configured || !self::isTestingName($actual)) {
            self::abort('Server reports current database "'.$actual.'" (configured "'.$configured.'").');
        }

        $version = (string) $connection->selectOne('SELECT VERSION() AS v')->v;
        if (strpos($version, self::REQUIRED_SERVER_VERSION) !== 0) {
            self::abort('Database server is "'.$version.'", expected "'.self::REQUIRED_SERVER_VERSION.'" (production).');
        }
    }

    private static function isTestingName(string $name): bool
    {
        return $name !== self::REQUIRED_SUFFIX
            && substr($name, -strlen(self::REQUIRED_SUFFIX)) === self::REQUIRED_SUFFIX;
    }

    private static function abort(string $reason): void
    {
        $message = 'Refusing to run tests: '.$reason.' Tests may only run against a database whose name ends with "'
            .self::REQUIRED_SUFFIX.'" (see .env.testing / phpunit.xml).';
        fwrite(STDERR, PHP_EOL.$message.PHP_EOL);

        throw new RuntimeException($message);
    }
}
