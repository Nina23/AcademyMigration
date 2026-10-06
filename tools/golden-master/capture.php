<?php

// Golden-master capture: GET every URL in urls.txt and store status, headers and body.
// Usage: php tools/golden-master/capture.php <label> [--base=http://localhost:8080] [--parallel=6]

$args = array_slice($argv, 1);
$label = null;
$base = 'http://localhost:8080';
$parallel = 6;
foreach ($args as $arg) {
    if (strpos($arg, '--base=') === 0) {
        $base = rtrim(substr($arg, 7), '/');
    } elseif (strpos($arg, '--parallel=') === 0) {
        $parallel = max(1, (int) substr($arg, 11));
    } else {
        $label = $arg;
    }
}
if (!$label || !preg_match('/^[A-Za-z0-9._-]+$/', $label)) {
    fwrite(STDERR, "Usage: php capture.php <label> [--base=URL] [--parallel=N]\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
$out = $root.'/storage/golden-master/'.$label;
if (is_dir($out)) {
    fwrite(STDERR, "Output already exists: $out\n");
    exit(1);
}
mkdir($out.'/bodies', 0777, true);

$token = getenv('GM_API_TOKEN') ?: '';

$entries = [];
foreach (file(__DIR__.'/urls.txt', FILE_IGNORE_NEW_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    $api = false;
    if (strpos($line, 'API ') === 0) {
        $api = true;
        $line = trim(substr($line, 4));
    }
    $entries[] = ['path' => $line, 'api' => $api];
}
if (!$token && array_filter($entries, function ($e) { return $e['api']; })) {
    fwrite(STDERR, "GM_API_TOKEN is not set; API URLs would only capture 401/302.\n");
    exit(1);
}

function makeHandle($base, $entry, $token)
{
    $ch = curl_init($base.$entry['path']);
    $headers = [];
    if ($entry['api']) {
        $headers[] = 'Accept: application/json';
        $headers[] = 'Authorization: Bearer '.$token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 300,
    ]);

    return $ch;
}

$index = fopen($out.'/index.tsv', 'w');
fwrite($index, "n\tstatus\tcontent_type\tlocation\tbytes\tpath\n");

$mh = curl_multi_init();
$queue = array_keys($entries);
$running = [];
$results = [];
$done = 0;
$total = count($entries);
$start = microtime(true);

while ($queue || $running) {
    while ($queue && count($running) < $parallel) {
        $n = array_shift($queue);
        $ch = makeHandle($base, $entries[$n], $token);
        curl_multi_add_handle($mh, $ch);
        $running[(int) $ch] = [$n, $ch];
    }
    curl_multi_exec($mh, $active);
    curl_multi_select($mh, 1.0);
    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        [$n, ] = $running[(int) $ch];
        unset($running[(int) $ch]);
        $raw = curl_multi_getcontent($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerText = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        $type = '';
        $location = '';
        if (preg_match('/^Content-Type:\s*(.+)$/mi', $headerText, $m)) {
            $type = trim($m[1]);
        }
        if (preg_match('/^Location:\s*(.+)$/mi', $headerText, $m)) {
            $location = trim($m[1]);
        }
        if ($info['result'] !== CURLE_OK) {
            $status = 'ERR:'.curl_error($ch);
        }
        file_put_contents(sprintf('%s/bodies/%04d.txt', $out, $n), $body);
        $results[$n] = [$n, $status, $type, $location, strlen($body), $entries[$n]['path']];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        $done++;
        fwrite(STDERR, sprintf("\r%d/%d", $done, $total));
    }
}
curl_multi_close($mh);

ksort($results);
foreach ($results as $row) {
    fwrite($index, implode("\t", $row)."\n");
}
fclose($index);

$statuses = array_count_values(array_map(function ($r) { return (string) $r[1]; }, $results));
ksort($statuses);
fwrite(STDERR, sprintf("\nCaptured %d URLs in %ds into %s\n", $total, microtime(true) - $start, $out));
foreach ($statuses as $s => $c) {
    fwrite(STDERR, "  $s: $c\n");
}
