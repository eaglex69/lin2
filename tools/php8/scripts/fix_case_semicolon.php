<?php
// PHP 8.5 deprecates "case X;" / "default;" (a semicolon instead of a colon after a switch label; removal is planned for
// PHP 9). The two forms are equivalent for the parser, so the semicolon is replaced by a colon (byte-exact edit by token offset).
// usage: node runphp.mjs 8.3 /tools/fix_case_semicolon.php /work [--dry]
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$dry = in_array('--dry', $argv, true);
$total = 0; $filesChanged = 0;
foreach (php8_list_files($root) as $f) {
    $src = file_get_contents($f);
    $toks = php8_tokens($src);
    $n = count($toks);
    $offsets = [];
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] === T_DEFAULT) {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][0] === null && $toks[$j][1] === ';') $offsets[] = [$toks[$j][3], $toks[$j][2]];
            continue;
        }
        if ($toks[$i][0] !== T_CASE) continue;
        $depth = 0; $ternary = 0;
        for ($j = $i + 1; $j < $n; $j++) {
            $t = $toks[$j];
            if ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES) { $depth++; continue; }
            if ($t[0] !== null) continue;
            $s = $t[1];
            if ($s === '(' || $s === '[' || $s === '{') $depth++;
            elseif ($s === ')' || $s === ']' || $s === '}') { $depth--; if ($depth < 0) break; }
            elseif ($depth === 0 && $s === '?') $ternary++;
            elseif ($depth === 0 && $s === ':') { if ($ternary > 0) $ternary--; else break; }
            elseif ($depth === 0 && $s === ';') { $offsets[] = [$t[3], $t[2]]; break; }
        }
    }
    if (!$offsets) continue;
    $rel = ltrim(substr($f, strlen($root)), '/');
    foreach ($offsets as [$off, $line]) {
        if ($src[$off] !== ';') { fwrite(STDERR, "unexpected byte at $rel:$line\n"); exit(2); }
        echo "$rel:$line\n";
    }
    if (!$dry) {
        foreach (array_reverse($offsets) as [$off]) $src[$off] = ':';
        file_put_contents($f, $src);
    }
    $total += count($offsets); $filesChanged++;
}
echo ($dry ? '[dry run] ' : '') . "$total label(s) in $filesChanged file(s)\n";
