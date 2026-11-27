<?php
// PHP 8 changed the comparison of a string with a number ("Saner string to number comparisons"): a NON-numeric string is
// no longer converted to 0, the number is converted to a string instead.  The game validates numeric form fields like
//
//     if ($_POST['gold'] <= 0 or ...) { echo 'error'; exit; }
//
// and relied on 'abc' <= 0 (and '-x' <= 0, ...) being TRUE in PHP 5.  In PHP 8 'abc' <= 0 is FALSE, the garbage passes the check
// and dies a few lines later with a TypeError in the arithmetic ('abc' * 100) - or, where nothing is calculated, goes into the database.
// PHP 5 compared the numeric VALUE of the string, so that is made explicit:
//
//     $_POST['gold'] <= 0      ->      (float)$_POST['gold'] <= 0
//
// Only a lone request value ($_POST['k'] / $_GET['k'] / $_REQUEST['k']) compared with a numeric literal is rewritten:
//   <  <=  >  >=  with any number, and  ==  !=  <>  with 0 (flags like  $_GET['act'] == 3  behave identically and are left alone).
// The pagination keys page/start are skipped (they are normalised with is_numeric() at the top of each page).
// usage: node runphp.mjs 8.3 /tools/fix_req_compare.php /work [--dry]
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$dry = in_array('--dry', $argv, true);
$skipKeys = ['page', 'start'];

$ordering = [T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL];
$equality = [T_IS_EQUAL, T_IS_NOT_EQUAL];
$isCmp = function (array $t) use ($ordering, $equality): ?string {
    if ($t[0] === null && ($t[1] === '<' || $t[1] === '>')) return 'ord';
    if (in_array($t[0], $ordering, true)) return 'ord';
    if (in_array($t[0], $equality, true)) return 'eq';
    return null;
};
// tokens that may stand right before the left operand of a comparison (so the comparison is not part of a bigger arithmetic/concat/cast expression)
$boundaryIds = [T_RETURN, T_ECHO, T_PRINT, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_INLINE_HTML, T_CLOSE_TAG,
                T_LOGICAL_OR, T_LOGICAL_AND, T_LOGICAL_XOR, T_BOOLEAN_OR, T_BOOLEAN_AND, T_IF, T_ELSEIF, T_WHILE];
$boundaryTxt = ['(', ',', ';', '{', '}', '=', '?', ':'];
$isBoundary = function (array $t) use ($boundaryIds, $boundaryTxt): bool {
    return $t[0] === null ? in_array($t[1], $boundaryTxt, true) : in_array($t[0], $boundaryIds, true);
};
// tokens that may follow the numeric literal (the literal is the whole right operand)
$afterIds = [T_LOGICAL_OR, T_LOGICAL_AND, T_LOGICAL_XOR, T_BOOLEAN_OR, T_BOOLEAN_AND, T_CLOSE_TAG];
$afterTxt = [')', ';', ',', '{', ']', '?', ':'];
$isAfter = function (array $t) use ($afterIds, $afterTxt): bool {
    return $t[0] === null ? in_array($t[1], $afterTxt, true) : in_array($t[0], $afterIds, true);
};
$isZero = fn(string $s): bool => (float)$s == 0.0 && preg_match('/^[0.]+$/', $s);

$total = 0; $filesChanged = 0;
foreach (php8_list_files($root) as $f) {
    $src = file_get_contents($f);
    $toks = php8_tokens($src);
    $ctx = php8_ctx_map($toks);
    $n = count($toks);
    $offsets = [];
    for ($i = 0; $i < $n; $i++) {
        if ($toks[$i][0] !== T_VARIABLE || !in_array($toks[$i][1], ['$_POST', '$_GET', '$_REQUEST'], true)) continue;
        if (($ctx[$i] ?? 'code') !== 'code') continue;
        // $_POST [ 'key' ]
        $a = php8_next_sig($toks, $i);
        if ($a < 0 || $toks[$a][1] !== '[') continue;
        $b = php8_next_sig($toks, $a);
        if ($b < 0 || $toks[$b][0] !== T_CONSTANT_ENCAPSED_STRING) continue;
        $c = php8_next_sig($toks, $b);
        if ($c < 0 || $toks[$c][1] !== ']') continue;
        $key = trim($toks[$b][1], '\'"');
        if (in_array($key, $skipKeys, true)) continue;
        $nx = php8_next_sig($toks, $c);
        $pv = php8_prev_sig($toks, $i);
        if ($nx < 0 || $pv < 0) continue;
        if ($toks[$nx][1] === '[' || $toks[$nx][0] === T_OBJECT_OPERATOR) continue;   // further indexing / member access

        $hit = false;
        // (1)  <boundary> $_POST['k'] <op> [-|+] number <after>
        if ($isBoundary($toks[$pv]) && ($kind = $isCmp($toks[$nx])) !== null) {
            $r = php8_next_sig($toks, $nx);
            $sign = false;
            if ($r >= 0 && $toks[$r][0] === null && ($toks[$r][1] === '-' || $toks[$r][1] === '+')) { $sign = true; $r = php8_next_sig($toks, $r); }
            if ($r >= 0 && ($toks[$r][0] === T_LNUMBER || $toks[$r][0] === T_DNUMBER)) {
                $after = php8_next_sig($toks, $r);
                if ($after >= 0 && $isAfter($toks[$after]) && ($kind === 'ord' || $isZero($toks[$r][1]))) $hit = true;
            }
        }
        // (2)  <boundary> [-|+] number <op> $_POST['k'] <after>
        if (!$hit && ($kind = $isCmp($toks[$pv])) !== null && $isAfter($toks[$nx])) {
            $r = php8_prev_sig($toks, $pv);
            if ($r >= 0 && ($toks[$r][0] === T_LNUMBER || $toks[$r][0] === T_DNUMBER)) {
                $l = php8_prev_sig($toks, $r);
                if ($l >= 0 && $toks[$l][0] === null && ($toks[$l][1] === '-' || $toks[$l][1] === '+')) $l = php8_prev_sig($toks, $l);
                if ($l >= 0 && $isBoundary($toks[$l]) && ($kind === 'ord' || $isZero($toks[$r][1]))) $hit = true;
            }
        }
        if ($hit) $offsets[] = [$toks[$i][3], $toks[$i][2], $toks[$i][1] . "['" . $key . "']"];
    }
    if (!$offsets) continue;
    $rel = ltrim(substr($f, strlen($root)), '/');
    foreach ($offsets as [$off, $line, $what]) {
        if (substr($src, $off, 1) !== '$') { fwrite(STDERR, "unexpected byte at $rel:$line\n"); exit(2); }
        echo "$rel:$line  $what\n";
    }
    if (!$dry) {
        foreach (array_reverse($offsets) as [$off]) $src = substr($src, 0, $off) . '(float)' . substr($src, $off);
        file_put_contents($f, $src);
    }
    $total += count($offsets); $filesChanged++;
}
echo ($dry ? '[dry run] ' : '') . "$total comparison(s) in $filesChanged file(s)\n";
