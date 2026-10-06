<?php

// Golden-master compare: php tools/golden-master/compare.php <labelA> <labelB>

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php compare.php <labelA> <labelB>\n");
    exit(1);
}
[, $a, $b] = $argv;
$root = dirname(__DIR__, 2).'/storage/golden-master';
$report = "$root/compare-$a-vs-$b";
if (!is_dir($report)) {
    mkdir($report, 0777, true);
}

function readIndex($dir)
{
    $rows = [];
    $lines = file("$dir/index.tsv", FILE_IGNORE_NEW_LINES);
    array_shift($lines);
    foreach ($lines as $line) {
        [$n, $status, $type, $location, , $path] = explode("\t", $line);
        $rows[$path] = compact('n', 'status', 'type', 'location');
    }

    return $rows;
}

function normalize($body)
{
    // Debugbar: head assets (injected right before </head>) and the body script (right before </body>).
    $body = preg_replace("#<link rel='stylesheet' type='text/css' property='stylesheet' href='[^']*/_debugbar/.*?(?=</head>)#s", '', $body);
    $body = preg_replace('#<script type="text/javascript">\s*var phpdebugbar = .*?</script>(?=\s*</body>)#s', '', $body);
    // CSRF tokens.
    $body = preg_replace('#(name="_token" value=")[^"]*"#', '$1__CSRF__"', $body);
    $body = preg_replace('#(<meta name="csrf-token" content=")[^"]*"#', '$1__CSRF__"', $body);
    $body = preg_replace('#("csrf_?[Tt]oken"\s*:\s*")[^"]*"#', '$1__CSRF__"', $body);

    return $body;
}

$ia = readIndex("$root/$a");
$ib = readIndex("$root/$b");
$counts = ['same' => 0, 'whitespace-only' => 0, 'DIFF' => 0, 'missing' => 0];
$lines = [];

foreach ($ia as $path => $ra) {
    if (!isset($ib[$path])) {
        $counts['missing']++;
        $lines[] = "missing in $b\t$path";
        continue;
    }
    $rb = $ib[$path];
    $problems = [];
    foreach (['status', 'type', 'location'] as $k) {
        if ($ra[$k] !== $rb[$k]) {
            $problems[] = "$k: {$ra[$k]} -> {$rb[$k]}";
        }
    }
    $ba = $bb = '';
    if ((int) $ra['status'] < 500 || (int) $rb['status'] < 500) {
        $ba = normalize(file_get_contents(sprintf('%s/%s/bodies/%04d.txt', $root, $a, $ra['n'])));
        $bb = normalize(file_get_contents(sprintf('%s/%s/bodies/%04d.txt', $root, $b, $rb['n'])));
    }
    $result = 'same';
    if ($problems || $ba !== $bb) {
        $result = 'DIFF';
        if (!$problems && preg_replace('/\s+/', '', $ba) === preg_replace('/\s+/', '', $bb)) {
            $result = 'whitespace-only';
        }
    }
    $counts[$result]++;
    if ($result !== 'same') {
        $file = sprintf('%s/%04d', $report, $ra['n']);
        file_put_contents("$file.a", $ba);
        file_put_contents("$file.b", $bb);
        $diff = shell_exec('git diff --no-index --no-color -U2 '.escapeshellarg("$file.a").' '.escapeshellarg("$file.b"));
        file_put_contents("$file.diff", ($problems ? implode("\n", $problems)."\n\n" : '').$diff);
        unlink("$file.a");
        unlink("$file.b");
        $lines[] = sprintf("%s\t%s\t%s%s", $result, $ra['n'], $path, $problems ? '  ['.implode('; ', $problems).']' : '');
    }
}
foreach ($ib as $path => $rb) {
    if (!isset($ia[$path])) {
        $lines[] = "new in $b\t$path";
    }
}

$summary = '';
foreach ($counts as $k => $v) {
    $summary .= "$k: $v\n";
}
file_put_contents("$report/report.txt", $summary."\n".implode("\n", $lines)."\n");
echo $summary;
echo implode("\n", array_slice($lines, 0, 200)), "\n";
echo "Report: $report/report.txt\n";
