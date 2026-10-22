<?php

/*
 * S3 checklist against MinIO (UPGRADE_PLAN.md O1 / O4, §8 "S3 checks").
 * Runs inside the app-s3 container (see README.md), on Laravel 8 / PHP 7.4 and
 * Laravel 9 / PHP 8.1 alike:
 *
 *   php tools/s3-test/run-checks.php main <label>   checks a-f and h (+ waits for the 5-minute expiry of c)
 *   php tools/s3-test/run-checks.php fail <label> <case>   check g: one upload while S3 is unavailable
 *   php tools/s3-test/run-checks.php fail-verify <label> <case>   check g: bucket after the failed upload
 *
 * Results: storage/app/s3-test/<label>-<phase>.txt (one "key = value" per line, diffable).
 *
 * Safety: refuses to run unless the database is akademija_s3test, the default disk is s3
 * and the S3 endpoint is the local MinIO. Creates a temporary superuser in akademija_s3test
 * with a random password and API token kept in memory only, and deletes it at the end.
 * Signatures, credentials and tokens in URLs are masked in the output.
 */

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$base = dirname(__DIR__, 2);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const BUCKET = 'akademija-s3test';
const ENDPOINT = 'http://localhost:9000';
const APP_HOST = 'localhost:8081';

$phase = $argv[1] ?? '';
$label = $argv[2] ?? '';
$case = $argv[3] ?? '';
if (!in_array($phase, ['main', 'fail', 'fail-verify'], true) || !preg_match('/^[a-z0-9-]+$/', $label)) {
    fwrite(STDERR, "usage: run-checks.php main|fail|fail-verify <label> [case]\n");
    exit(2);
}

// ---------------------------------------------------------------- guards
$connection = config('database.default');
$guards = [
    'database is akademija_s3test' => config("database.connections.$connection.database") === 'akademija_s3test',
    'default disk is s3' => config('filesystems.default') === 's3',
    's3 endpoint is the local MinIO' => config('filesystems.disks.s3.endpoint') === ENDPOINT,
    's3 bucket is the test bucket' => config('filesystems.disks.s3.bucket') === BUCKET,
    'APP_URL is the s3 test app' => config('app.url') === 'http://'.APP_HOST,
];
foreach ($guards as $what => $ok) {
    if (!$ok) {
        fwrite(STDERR, "REFUSED: $what is false\n");
        exit(3);
    }
}

// ---------------------------------------------------------------- output
$results = [];
function out(string $key, $value): void
{
    global $results;
    if (is_bool($value)) {
        $value = $value ? 'yes' : 'no';
    } elseif ($value === null) {
        $value = '(null)';
    } elseif (is_array($value)) {
        $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $value = str_replace(["\r", "\n"], ' ', (string) $value);
    $results[] = "$key = $value";
    echo "$key = $value\n";
}

/** Masks signatures, credentials, Croppa tokens and dates in a URL. */
function mask(string $url): string
{
    $url = preg_replace('/([?&](?:X-Amz-Credential|X-Amz-Signature|X-Amz-Security-Token|X-Amz-Date|token)=)[^&"\s]+/i', '$1…', $url);

    return $url;
}

function query_param(string $url, string $name): ?string
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

    return $q[$name] ?? null;
}

// ---------------------------------------------------------------- clients
$s3 = new S3Client([
    'version' => 'latest',
    'region' => config('filesystems.disks.s3.region'),
    'endpoint' => ENDPOINT,
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key' => config('filesystems.disks.s3.key'),
        'secret' => config('filesystems.disks.s3.secret'),
    ],
]);

/** Object metadata straight from MinIO (not through the app's adapter). */
function head(string $key): ?array
{
    global $s3;
    try {
        $r = $s3->headObject(['Bucket' => BUCKET, 'Key' => $key]);

        return ['type' => $r['ContentType'], 'size' => $r['ContentLength'], 'etag' => trim($r['ETag'], '"'),
            'modified' => (string) $r['LastModified']];
    } catch (Aws\S3\Exception\S3Exception $e) {
        return null;
    }
}

function keys(string $prefix): array
{
    global $s3;
    $keys = [];
    foreach ($s3->getPaginator('ListObjectsV2', ['Bucket' => BUCKET, 'Prefix' => $prefix]) as $page) {
        foreach ($page['Contents'] ?? [] as $o) {
            $keys[] = $o['Key'];
        }
    }
    sort($keys);

    return $keys;
}

function acl(string $key): string
{
    global $s3;
    try {
        $grants = [];
        foreach ($s3->getObjectAcl(['Bucket' => BUCKET, 'Key' => $key])['Grants'] as $g) {
            $who = $g['Grantee']['URI'] ?? ($g['Grantee']['Type'] ?? '?');
            $grants[] = basename($who).':'.$g['Permission'];
        }

        return implode(',', $grants);
    } catch (Aws\S3\Exception\S3Exception $e) {
        return 'error '.$e->getAwsErrorCode();
    }
}

$jar = new CookieJar();
$http = new Client(['http_errors' => false, 'allow_redirects' => false, 'timeout' => 60, 'cookies' => $jar]);

/**
 * HTTP request as a browser on http://localhost:8081 would make it. App URLs go to
 * Apache in this container (port 80) with Host: localhost:8081; MinIO URLs go to MinIO.
 */
function req(string $method, string $url, array $options = [])
{
    global $http;
    if (Str::startsWith($url, '/') || Str::startsWith($url, 'http://'.APP_HOST)) {
        $path = Str::startsWith($url, '/') ? $url : substr($url, strlen('http://'.APP_HOST));
        $options['headers'] = ($options['headers'] ?? []) + ['Host' => APP_HOST];
        $url = 'http://127.0.0.1:80'.$path;
    }

    return $http->request($method, $url, $options);
}

/** Fetches a URL and returns "status content-type bytes". */
function fetch(string $url): string
{
    $r = req('GET', $url);

    return $r->getStatusCode().' '.$r->getHeaderLine('Content-Type').' '.strlen((string) $r->getBody()).'B';
}

// ---------------------------------------------------------------- temporary superuser (DB copy only)
$password = Str::random(40);
$token = (string) Str::uuid();
$userId = DB::table('users')->insertGetId([
    'email' => 's3test-'.Str::lower(Str::random(8)).'@example.invalid',
    'password' => Hash::make($password),
    'activated' => 1,
    'superuser' => 1,
    'privacy_policy_accepted' => 1,
    'first_name' => 'S3',
    'last_name' => 'Test',
    'locale' => 'en',
    'api_token' => $token,
    'email_verified_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);
$userEmail = DB::table('users')->where('id', $userId)->value('email');
register_shutdown_function(function () use ($userId) {
    DB::table('users')->where('id', $userId)->delete();
});
$api = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'];

/** Upload as the admin file manager (FileManager.vue dropzoneSending) does it. */
function upload(string $filename, string $bytes, string $mime)
{
    global $api;
    $multipart = [
        ['name' => 'name', 'contents' => $bytes, 'filename' => $filename, 'headers' => ['Content-Type' => $mime]],
        ['name' => 'folder_id', 'contents' => ''],
    ];
    foreach (locales() as $locale) {
        $multipart[] = ['name' => "description[$locale]", 'contents' => ''];
        $multipart[] = ['name' => "alt_attribute[$locale]", 'contents' => ''];
    }

    return req('POST', '/api/files', ['headers' => $api, 'multipart' => $multipart]);
}

function jpeg(int $w, int $h): string
{
    $img = imagecreatetruecolor($w, $h);
    for ($x = 0; $x < $w; $x += 20) {
        imagefilledrectangle($img, $x, 0, $x + 19, $h, imagecolorallocate($img, ($x * 7) % 256, 90, 200 - ($x % 200)));
    }
    ob_start();
    imagejpeg($img, null, 90);

    return ob_get_clean();
}

$pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";
@mkdir(storage_path('app/s3-test'), 0775, true);

out('env.php', PHP_VERSION);
out('env.laravel', app()->version());
out('env.croppa_crops_disk', config('croppa.crops_disk', config('croppa.crops_dir')));

// ================================================================ g) failure during upload
if ($phase === 'fail' || $phase === 'fail-verify') {
    $name = 's3-fail-'.$case;
    if ($phase === 'fail') {
        $before = DB::table('files')->count();
        $r = upload("S3 Fail $case.jpg", jpeg(400, 300), 'image/jpeg');
        out("g.$case.upload.status", $r->getStatusCode());
        out("g.$case.upload.body", Str::limit((string) $r->getBody(), 200));
        out("g.$case.db.files_rows_added", DB::table('files')->count() - $before);
        out("g.$case.db.rows_with_name", DB::table('files')->where('name', 'like', "$name%")->count());
    } else {
        out("g.$case.bucket.keys", keys("files/$name"));
        out("g.$case.db.rows_with_name", DB::table('files')->where('name', 'like', "$name%")->count());
    }
    file_put_contents(storage_path("app/s3-test/$label-$phase-$case.txt"), implode("\n", $results)."\n");
    exit(0);
}

// ================================================================ a) uploads
$photo = jpeg(800, 600);
$uploads = [
    'image' => ['S3 Test Photo.jpg', $photo, 'image/jpeg'],
    'image_dup' => ['S3 Test Photo.jpg', jpeg(400, 300), 'image/jpeg'],
    'pdf' => ['S3 Test Document.pdf', $pdf, 'application/pdf'],
];
$models = [];
foreach ($uploads as $k => [$filename, $bytes, $mime]) {
    $r = upload($filename, $bytes, $mime);
    out("a.$k.status", $r->getStatusCode());
    $m = json_decode((string) $r->getBody(), true)['model'] ?? [];
    $models[$k] = $m;
    out("a.$k.db", array_intersect_key($m, array_flip(['id', 'type', 'name', 'path', 'extension', 'mimetype', 'width', 'height', 'filesize'])));
    $key = $m['path'] ?? '';
    $h = $key ? head($key) : null;
    out("a.$k.object_exists", $h !== null);
    out("a.$k.object.content_type", $h['type'] ?? null);
    out("a.$k.object.size_matches_upload", $h !== null && (int) $h['size'] === strlen($bytes));
    out("a.$k.object.acl (GetObjectAcl)", $key ? acl($key) : null);
    try {
        out("a.$k.Storage::getVisibility", Storage::getVisibility($key));
    } catch (Throwable $e) {
        out("a.$k.Storage::getVisibility", 'exception '.get_class($e));
    }
    out("a.$k.anonymous_get", $key ? fetch(ENDPOINT.'/'.BUCKET.'/'.$key) : null);
}
out('a.bucket.files_keys', keys('files/s3-test'));
$image = $models['image'];
$pdfModel = $models['pdf'];

// ================================================================ b) Storage::url() links
out('b.Storage::url(image)', Storage::url($image['path']));
out('b.api_model_url(image)', $image['url'] ?? null);
out('b.api_model_url(pdf)', $pdfModel['url'] ?? null);

// admin file form (session login as the temporary user)
$login = req('GET', '/en/login');
preg_match('/name="_token" value="([^"]+)"/', (string) $login->getBody(), $mt);
$r = req('POST', '/en/login', ['form_params' => ['email' => $userEmail, 'password' => $password, '_token' => $mt[1] ?? '']]);
out('b.admin.login', $r->getStatusCode().' -> '.$r->getHeaderLine('Location'));
foreach (['image' => $image, 'pdf' => $pdfModel] as $k => $m) {
    $r = req('GET', "/admin/files/{$m['id']}/edit");
    preg_match('/<th class="w-25">[^<]*<\/th>\s*<td>\s*<div[^>]*>\s*<a href="([^"]+)"/', (string) $r->getBody(), $ma);
    $link = $ma[1] ?? '';
    out("b.admin.edit_form.$k", $r->getStatusCode().' link='.$link);
    out("b.admin.edit_form.$k.link_opens", $link ? fetch($link) : null);
}

// public page with documents: attach the PDF to the "akademija/dokumenti" page (DB copy only)
$pageId = DB::table('pages')->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(uri, '$.sr')) = 'akademija/dokumenti'")->value('id');
$pageType = DB::table('model_has_files')->where('model_id', $pageId)->where('model_type', 'like', '%Page')->value('model_type')
    ?? 'TypiCMS\Modules\Pages\Models\Page';
DB::table('model_has_files')->insert(['file_id' => $pdfModel['id'], 'model_type' => $pageType, 'model_id' => $pageId, 'position' => 9999]);
$r = req('GET', '/akademija/dokumenti');
$html = (string) $r->getBody();
out('b.public_page', '/akademija/dokumenti '.$r->getStatusCode());
$pdfName = basename($pdfModel['path']);
preg_match_all('/href="([^"]*'.preg_quote($pdfName, '/').'[^"]*)"/', $html, $links);
$links = array_map('html_entity_decode', $links[1]);
$visible = null;
$temporary = null;
foreach ($links as $l) {
    if (Str::contains($l, 'X-Amz-')) {
        $temporary = $temporary ?? $l;
    } else {
        $visible = $visible ?? $l;
    }
}
out('b.public_documents.visible_link', $visible);
out('b.public_documents.visible_link_opens', $visible ? fetch($visible) : null);

// ================================================================ c) temporaryUrl() (inside the HTML comment of _documents)
$generatedAt = time();
out('c.temporary_url_in_page', $temporary !== null);
if ($temporary) {
    out('c.temporary_url', mask($temporary));
    out('c.X-Amz-Expires', query_param($temporary, 'X-Amz-Expires'));
    out('c.X-Amz-Algorithm', query_param($temporary, 'X-Amz-Algorithm'));
    out('c.opens_now', fetch($temporary));
}

// ================================================================ d) Croppa
// d1: an uploaded image through a Croppa URL (S3 source)
$cropUrl = Croppa::url('storage/'.$image['path'], 240, 180);
$cropKey = 'files/'.pathinfo($image['path'], PATHINFO_FILENAME).'-240x180.'.pathinfo($image['path'], PATHINFO_EXTENSION);
out('d1.croppa_url', mask($cropUrl));
out('d1.crop_before', head($cropKey) !== null);
$r = req('GET', $cropUrl);
$location1 = $r->getHeaderLine('Location');
out('d1.first.status', $r->getStatusCode());
out('d1.first.location', mask($location1));
$crop = head($cropKey);
out('d1.crop_stored', $crop !== null);
out('d1.crop.content_type', $crop['type'] ?? null);
out('d1.crop.acl', $crop ? acl($cropKey) : null);
if ($crop) {
    $size = getimagesizefromstring((string) req('GET', ENDPOINT.'/'.BUCKET.'/'.$cropKey)->getBody());
    out('d1.crop.dimensions', $size ? $size[0].'x'.$size[1] : null);
}
out('d1.location_target', $location1 ? fetch($location1) : null);
sleep(2);
$r = req('GET', $cropUrl);
out('d1.second.status', $r->getStatusCode());
out('d1.second.location_same', $r->getHeaderLine('Location') === $location1);
$crop2 = head($cropKey);
out('d1.second.crop_reused (same etag+mtime)', $crop && $crop2 && $crop['etag'] === $crop2['etag'] && $crop['modified'] === $crop2['modified']);
out('d1.local_tmp_left', glob(storage_path('app/{,*/,*/*/}*s3-test-photo*'), GLOB_BRACE) ?: []);

// d2 + h: core presenter / API thumb (admin list announcements, append=thumb)
out('d2.img_not_found_before', keys('img-not-found'));
$listUrl = '/api/announcements?sort=title_translated&fields[announcements]=id,image_id,status,title&include=image&append=thumb&page=1&per_page=50';
$first = json_decode((string) req('GET', $listUrl, ['headers' => $api])->getBody(), true)['data'][0] ?? null;
DB::table('announcements')->where('id', $first['id'])->update(['image_id' => $image['id']]);
$r = req('GET', $listUrl, ['headers' => $api]);
$rows = json_decode((string) $r->getBody(), true)['data'] ?? [];
$row = collect($rows)->firstWhere('id', $first['id']);
out('h.list.status', $r->getStatusCode());
out('h.list.rows', count($rows));
out('h.list.rows_with_thumb_key', count(array_filter($rows, function ($x) { return array_key_exists('thumb', $x); })));
out('h.list.distinct_thumbs', array_values(array_unique(array_map(function ($x) { return mask($x['thumb'] ?? '(none)'); }, $rows))));
out('d2.announcement_with_uploaded_image.thumb', mask($row['thumb'] ?? '(none)'));
$thumb = $row['thumb'] ?? '';
if ($thumb) {
    $r = req('GET', $thumb);
    $l = $r->getHeaderLine('Location');
    out('d2.thumb.first.status', $r->getStatusCode());
    out('d2.thumb.first.location', mask($l));
    out('d2.thumb.location_target', $l ? fetch($l) : null);
    $r = req('GET', $thumb);
    out('d2.thumb.second', $r->getStatusCode().' location_same='.($r->getHeaderLine('Location') === $l ? 'yes' : 'no'));
}
out('d2.img_not_found_after', keys('img-not-found'));
foreach (keys('img-not-found') as $k) {
    out("d2.$k.content_type", head($k)['type'] ?? null);
}

// ================================================================ e) file manager thumbnails
$r = req('GET', '/api/files?folder_id=', ['headers' => $api]);
out('e.file_manager.list_status (root, has folders)', $r->getStatusCode());
$list = json_decode((string) $r->getBody(), true)['models'] ?? [];
$folder = collect($list)->firstWhere('type', 'f');
out('e.file_manager.folder.url', $folder ? ($folder['url'] ?? '(missing)') : '(no folder)');
foreach (['image' => $image, 'image_dup' => $models['image_dup'], 'pdf' => $pdfModel] as $k => $m) {
    $e = collect($list)->firstWhere('id', $m['id']);
    out("e.file_manager.$k.thumb_sm", $e['thumb_sm'] ?? '(missing)');
    out("e.file_manager.$k.thumb_sm_opens", !empty($e['thumb_sm']) ? fetch($e['thumb_sm']) : null);
}

// ================================================================ f) delete
$before = keys('files/s3-test-photo');
out('f.keys_before_delete', $before);
$r = req('DELETE', "/api/files/{$image['id']}", ['headers' => $api]);
out('f.delete.status', $r->getStatusCode());
out('f.db_row_removed', DB::table('files')->where('id', $image['id'])->doesntExist());
out('f.keys_after_delete', keys('files/s3-test-photo'));
foreach (['image_dup' => $models['image_dup'], 'pdf' => $pdfModel] as $k => $m) {
    $r = req('DELETE', "/api/files/{$m['id']}", ['headers' => $api]);
    out("f.delete_$k", $r->getStatusCode().' remaining='.json_encode(keys($m['path'])));
}
DB::table('model_has_files')->where('file_id', $pdfModel['id'])->delete();

// ================================================================ c) expiry (last: waits ~5 minutes)
if ($temporary) {
    out('c.opens_after_delete (object gone)', fetch($temporary));
    $r = upload('S3 Test Document.pdf', $pdf, 'application/pdf');   // same key again
    $again = json_decode((string) $r->getBody(), true)['model'] ?? [];
    out('c.reupload_same_key', ($again['path'] ?? '') === $pdfModel['path']);
    out('c.opens_before_expiry', fetch($temporary));
    $wait = $generatedAt + 300 + 5 - time();
    if ($wait > 0 && getenv('S3TEST_NO_WAIT')) {
        $wait = 0;   // debugging only: skip the 5-minute wait
    }
    if ($wait > 0) {
        sleep($wait);
    }
    out('c.opens_after_expiry ('.(time() - $generatedAt).'s)', fetch($temporary));
    $body = (string) req('GET', $temporary)->getBody();
    preg_match('/<Code>([^<]+)<\/Code>.*?<Message>([^<]+)<\/Message>/s', $body, $me);
    out('c.after_expiry.error', isset($me[1]) ? $me[1].': '.$me[2] : Str::limit($body, 120));
    if (!empty($again['id'])) {
        req('DELETE', "/api/files/{$again['id']}", ['headers' => $api]);
    }
}
out('end.bucket.files_keys', keys('files/s3-test'));

file_put_contents(storage_path("app/s3-test/$label-main.txt"), implode("\n", $results)."\n");
