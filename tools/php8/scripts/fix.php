<?php
// Mechanical PHP 8 fixes, token based (byte exact):
//   1. short open tags  "<?"  ->  "<?php"
//   2. bare words that PHP <= 7 silently treated as strings ("undefined constant") -> quoted strings
// Usage: php fix.php /work [--dry]
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';

$root = rtrim($argv[1] ?? '/work', '/');
$dry  = in_array('--dry', $argv, true);
$files = php8_list_files($root);
$known = php8_known_constants();
$known['SID'] = true;           // defined by ext/session once session_start() ran (db.php does that)

// project-defined constants (define('X', ...))
$projConsts = [];
foreach ($files as $f) {
    $toks = php8_tokens(file_get_contents($f)); $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] === T_STRING && strtolower($toks[$i][1]) === 'define') {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][1] === '(') {
                $k = php8_next_sig($toks, $j);
                if ($k >= 0 && $toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $projConsts[trim($toks[$k][1], "'\"")] = true;
            }
        }
    }
}

$totTags = 0; $totConst = 0; $changedFiles = 0; $stat = [];
foreach ($files as $f) {
    $code = file_get_contents($f);
    $toks = php8_tokens($code);
    $edits = [];   // [offset, length, replacement]

    // 1. short open tags
    foreach ($toks as $i => [$id, $text, $line, $off]) {
        if ($id === T_OPEN_TAG && $text === '<?') {
            $next = $code[$off + 2] ?? '';
            $rep = ($next === '' || strpos(" \t\r\n", $next) !== false) ? '<?php' : '<?php ';
            $edits[] = [$off, 2, $rep]; $totTags++;
        }
    }
    // 2. bare words -> strings
    foreach (php8_classify($toks) as $r) {
        if ($r['kind'] !== 'const') continue;
        if (isset($known[$r['name']]) || isset($projConsts[$r['name']])) continue;
        $edits[] = [$r['off'], strlen($r['name']), "'" . $r['name'] . "'"];
        $totConst++;
        $stat[$r['name']] = ($stat[$r['name']] ?? 0) + 1;
    }
    if (!$edits) continue;
    usort($edits, fn($a, $b) => $b[0] <=> $a[0]);
    $new = $code;
    foreach ($edits as [$o, $l, $rep]) $new = substr_replace($new, $rep, $o, $l);
    $changedFiles++;
    if (!$dry) file_put_contents($f, $new);
}
echo ($dry ? "[dry-run] " : "") . "files changed: $changedFiles, short tags fixed: $totTags, bare-word constants quoted: $totConst, distinct names: " . count($stat) . "\n";
