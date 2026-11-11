<?php

/*
 * TEST SERVER ONLY — prepares the imported database COPY before anyone else opens the site
 * (docker/test-server/README.md, "Prepare the copy"). Never on dev or production: it refuses to run
 * unless APP_ENV is "staging", the database host is the project's "db" service, the database name
 * matches --database, and that name is not one of the dev / test databases.
 *
 * In one transaction:
 *   a. adds one new test superadmin (password read from STDIN, stored only as a bcrypt hash);
 *   b. gives every other user a new random test password (only the hash is stored);
 *   c. invalidates login tokens: remember_token = NULL and a new random api_token for every
 *      existing user (the column is NOT NULL and unique, so it is replaced, not emptied), and
 *      empties the password reset table;
 *   d. checks before committing that the only new row is the superadmin and that for every existing
 *      user only password, remember_token and api_token differ; after committing, checks that no
 *      other table changed (CHECKSUM TABLE before / after).
 * Writes go through the query builder: no model events, so no history rows, no observers.
 *
 * Run it through tools/test-server/prepare-test-copy.sh (on the host, as root), which reads the
 * superadmin password without echo and writes STDOUT to a root-only credentials file.
 *   STDIN:  the test superadmin's password (one line)
 *   STDOUT: "email<TAB>test password" for every existing user — nothing else
 *   STDERR: the report (never a password)
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$base = dirname(__DIR__, 2);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function report(string $line): void
{
    fwrite(STDERR, $line."\n");
}

function fail(string $line, int $code = 1): void
{
    report('STOP: '.$line);
    exit($code);
}

$opt = getopt('', ['database:', 'admin-email:', 'admin-first-name:', 'admin-last-name:']);
foreach (['database', 'admin-email', 'admin-first-name', 'admin-last-name'] as $k) {
    if (empty($opt[$k]) || !is_string($opt[$k])) {
        fail("--$k is required");
    }
}

// ---------------------------------------------------------------- guards
$connection = config('database.default');
$dbName = config("database.connections.$connection.database");
$dbHost = config("database.connections.$connection.host");
if (getenv('TEST_COPY_CONFIRM') !== 'yes') {
    fail('run it through tools/test-server/prepare-test-copy.sh');
}
if (!app()->environment('staging')) {
    fail('APP_ENV is "'.app()->environment().'", not "staging"');
}
if ($dbHost !== 'db') {
    fail("database host is \"$dbHost\", not the test server's \"db\" service");
}
if ($dbName !== $opt['database']) {
    fail("connected database is \"$dbName\", not --database=\"{$opt['database']}\"");
}
if (in_array($dbName, ['akademija', 'akademija_testing', 'akademija_s3test'], true)) {
    fail("\"$dbName\" is a dev / test database name, not the test-server copy");
}
$adminEmail = strtolower(trim($opt['admin-email']));
if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    fail('--admin-email is not a valid e-mail address');
}
$adminPassword = rtrim((string) fgets(STDIN), "\r\n");
if (mb_strlen($adminPassword) < 12) {
    fail('the superadmin password must have at least 12 characters');
}

// Runs once per import: the marker lives in the storage volume (not in the database, so the copy's
// tables stay as described). After a re-import, the README removes it before preparing again.
$marker = storage_path('app/test-copy-prepared.json');
if (is_file($marker)) {
    $m = json_decode((string) file_get_contents($marker), true) ?: [];
    fail('this copy was already prepared ('.($m['prepared_at'] ?? '?').', superadmin '.($m['admin_email'] ?? '?').'). '
        .'Nothing changed. Run it again only after a fresh import (README section 5).');
}
$prefix = DB::connection()->getTablePrefix();
if (DB::table('users')->whereRaw('LOWER(email) = ?', [$adminEmail])->exists()) {
    fail("a user with the e-mail $adminEmail already exists. Nothing changed.");
}

// ---------------------------------------------------------------- snapshot before
$tables = array_map(fn ($r) => array_values((array) $r)[0], DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));
$checksum = function () use ($tables) {
    $out = [];
    foreach ($tables as $t) {
        $out[$t] = DB::selectOne('CHECKSUM TABLE `'.str_replace('`', '``', $t).'` EXTENDED')->Checksum;
    }

    return $out;
};
$before = $checksum();
$usersBefore = DB::table('users')->orderBy('id')->get()->keyBy('id')->map(fn ($u) => (array) $u)->all();
$resetsBefore = DB::table('password_resets')->count();
$changed = ['password', 'remember_token', 'api_token'];

// ---------------------------------------------------------------- changes (one transaction)
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
$randomPassword = function () use ($alphabet) {
    $p = '';
    for ($i = 0; $i < 20; $i++) {
        $p .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $p;
};

$credentials = [];
DB::beginTransaction();
try {
    $now = now()->format('Y-m-d H:i:s');
    $adminId = DB::table('users')->insertGetId([
        'email' => $adminEmail,
        'password' => Hash::make($adminPassword),
        'activated' => 1,
        'superuser' => 1,
        'privacy_policy_accepted' => 1,
        'first_name' => $opt['admin-first-name'],
        'last_name' => $opt['admin-last-name'],
        'api_token' => (string) Str::uuid(),
        'email_verified_at' => $now,
        'remember_token' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    unset($adminPassword);

    foreach ($usersBefore as $id => $u) {
        $plain = $randomPassword();
        DB::table('users')->where('id', $id)->update([
            'password' => Hash::make($plain),
            'remember_token' => null,
            'api_token' => (string) Str::uuid(),
        ]);
        $credentials[$u['email']] = $plain;
    }
    DB::table('password_resets')->delete();

    // ---- check before committing
    $usersAfter = DB::table('users')->orderBy('id')->get()->keyBy('id')->map(fn ($u) => (array) $u)->all();
    $problems = [];
    $newIds = array_map('intval', array_values(array_diff(array_keys($usersAfter), array_keys($usersBefore))));
    if ($newIds !== [(int) $adminId] || strtolower($usersAfter[$adminId]['email'] ?? '') !== $adminEmail) {
        $problems[] = 'the new rows are not exactly the one superadmin';
    }
    if (array_diff(array_keys($usersBefore), array_keys($usersAfter))) {
        $problems[] = 'an existing user row is missing';
    }
    foreach ($usersBefore as $id => $u) {
        $a = $usersAfter[$id] ?? [];
        foreach ($u as $col => $value) {
            if (!in_array($col, $changed, true) && (!array_key_exists($col, $a) || $a[$col] !== $value)) {
                $problems[] = "user $id: column $col changed";
            }
        }
        if (($a['password'] ?? '') === $u['password'] || !Hash::check($credentials[$u['email']], $a['password'] ?? '')) {
            $problems[] = "user $id: password not replaced";
        }
        if (!array_key_exists('remember_token', $a) || $a['remember_token'] !== null) {
            $problems[] = "user $id: remember_token not empty";
        }
        if (($a['api_token'] ?? '') === $u['api_token']) {
            $problems[] = "user $id: api_token not replaced";
        }
    }
    if (DB::table('password_resets')->count() !== 0) {
        $problems[] = 'password reset table not empty';
    }
    if ($problems) {
        DB::rollBack();
        fail("nothing changed (rolled back):\n  ".implode("\n  ", $problems), 2);
    }
    DB::commit();
} catch (Throwable $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    fail('nothing changed (rolled back): '.get_class($e).': '.$e->getMessage(), 2);
}

// ---------------------------------------------------------------- after commit: other tables unchanged
$after = $checksum();
$expected = [$prefix.'users', $prefix.'password_resets'];
$otherChanged = [];
foreach ($before as $t => $sum) {
    $isExpected = in_array($t, $expected, true);
    if (!$isExpected && $after[$t] !== $sum) {
        $otherChanged[] = $t;
    }
}

file_put_contents($marker, json_encode([
    'prepared_at' => now()->toIso8601String(),
    'database' => $dbName,
    'admin_email' => $adminEmail,
    'existing_users' => count($usersBefore),
], JSON_PRETTY_PRINT)."\n");

// ---------------------------------------------------------------- output
ksort($credentials);
foreach ($credentials as $email => $plain) {
    fwrite(STDOUT, $email."\t".$plain."\n");
}

report('Prepared the test copy "'.$dbName.'":');
report('  new test superadmin: id '.$adminId.', '.$adminEmail.' (activated, superuser, e-mail verified)');
report('  existing users: '.count($usersBefore).' — new test password (hash stored), remember_token emptied, new api_token; all other columns unchanged (checked)');
report('  password reset rows removed: '.$resetsBefore);
report('  credentials written to STDOUT: '.count($credentials).' lines (email, test password)');
report('  other tables: '.(count($tables) - 2).' checked with CHECKSUM TABLE, '.($otherChanged ? 'CHANGED: '.implode(', ', $otherChanged) : 'none changed'));
exit($otherChanged ? 3 : 0);
