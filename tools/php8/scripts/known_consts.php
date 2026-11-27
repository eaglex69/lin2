<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$known = php8_known_constants();
$byName = [];
foreach (php8_list_files($root) as $f) {
    $toks = php8_tokens(file_get_contents($f));
    foreach (php8_classify($toks) as $r) {
        if ($r['kind'] === 'const' && isset($known[$r['name']])) {
            $byName[$r['name']][$r['sub']] = ($byName[$r['name']][$r['sub']] ?? 0) + 1;
            $byName[$r['name']]['files'][ltrim(substr($f, strlen($root)), '/')] = 1;
        }
    }
}
ksort($byName);
foreach ($byName as $n => $v) {
    $files = array_keys($v['files']); unset($v['files']);
    echo str_pad($n, 24), json_encode($v), '  ', implode(',', array_slice($files, 0, 4)), count($files) > 4 ? ',…' : '', "\n";
}
