<?php
// PHP 8 changed loose comparison of numbers with non-numeric strings: 0 == 'abc' used to be TRUE, now FALSE
// ("Saner string to number comparisons"). Lists comparisons of an array element with the integer literal 0
// ($row['col'] == 0, != 0, <> 0) and shows what type the column has in shema.sql. Text columns (varchar/char/text/enum)
// holding words give a different result than on PHP 5.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');

$types = [];   // column => [type => true]
$table = null;
foreach (file($root . '/shema.sql') as $line) {
    if (preg_match('/^CREATE TABLE (?:IF NOT EXISTS )?`([^`]+)`/', $line, $m)) { $table = $m[1]; continue; }
    if ($table !== null && preg_match('/^\s*`([^`]+)`\s+([a-z]+)/i', $line, $m)) $types[$m[1]][strtolower($m[2])] = true;
    if ($table !== null && preg_match('/^\)/', $line)) $table = null;
}
$textTypes = ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'enum', 'set'];

$rows = []; $count = 0;
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $toks = php8_tokens(file_get_contents($f));
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        if (!in_array($toks[$i][0], [T_IS_EQUAL, T_IS_NOT_EQUAL], true)) continue;     // == and != / <>
        $l = php8_prev_sig($toks, $i); $r = php8_next_sig($toks, $i);
        if ($l < 0 || $r < 0) continue;
        $key = null;
        // right side: 0   left side: ...['key']
        if ($toks[$r][0] === T_LNUMBER && $toks[$r][1] === '0' && $toks[$l][1] === ']') {
            $k = php8_prev_sig($toks, $l);
            if ($k >= 0 && $toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $key = trim($toks[$k][1], "'\"");
        }
        // left side: 0   right side: $x['key']
        if ($key === null && $toks[$l][0] === T_LNUMBER && $toks[$l][1] === '0' && $toks[$r][0] === T_VARIABLE) {
            $a = php8_next_sig($toks, $r); $b = $a >= 0 ? php8_next_sig($toks, $a) : -1;
            if ($b >= 0 && $toks[$a][1] === '[' && $toks[$b][0] === T_CONSTANT_ENCAPSED_STRING) $key = trim($toks[$b][1], "'\"");
        }
        if ($key === null) continue;
        $t = array_keys($types[$key] ?? []);
        $isText = (bool) array_intersect($t, $textTypes);
        $rows[$rel][] = [$toks[$i][2], $key, $t ? implode('/', $t) : '(not in schema)', $isText];
        $count++;
    }
}
$textHits = 0;
foreach ($rows as $items) foreach ($items as $it) if ($it[3]) $textHits++;
echo "comparisons with integer 0: $count, of them on text columns: $textHits\n";
echo "\n== on text columns (result differs from PHP 5 when the value is a non-numeric word) ==\n";
foreach ($rows as $rel => $items) foreach ($items as $it) if ($it[3]) printf("  %s:%d  ['%s'] %s\n", $rel, $it[0], $it[1], $it[2]);
echo "\n== keys not in the schema (not checked) ==\n";
$seen = [];
foreach ($rows as $rel => $items) foreach ($items as $it) if ($it[2] === '(not in schema)' && !isset($seen[$it[1]])) { $seen[$it[1]] = 1; printf("  %s:%d  ['%s']\n", $rel, $it[0], $it[1]); }
