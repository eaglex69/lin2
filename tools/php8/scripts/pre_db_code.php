<?php
// Entry points that run PHP code BEFORE they include inc/db.php (which loads inc/php8.php: error level, mysql_* layer)
// or inc/php8.php itself.
// Such code runs with the server's error level; on PHP 8 an undefined $_GET key there prints a warning (when the server
// has display_errors on) before session_start()/header() and breaks the page.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';
$root = rtrim($argv[1] ?? '/work', '/');
$skipOps = ['define', 'include', 'include_once', 'require', 'require_once'];
$flagged = []; $noDb = 0; $clean = 0;
foreach (php8_list_files($root) as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $toks = php8_tokens(file_get_contents($f));
    $n = count($toks);
    // position of the first include of db.php
    $dbPos = -1;
    for ($i = 0; $i < $n; $i++) {
        if (in_array($toks[$i][0], [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
            for ($j = $i + 1; $j < $n && !($toks[$j][0] === null && $toks[$j][1] === ';'); $j++) {
                if ($toks[$j][0] === T_CONSTANT_ENCAPSED_STRING && preg_match('~inc/db\.php~', $toks[$j][1])) { $dbPos = $i; break 2; }
            }
        }
    }
    if ($dbPos < 0) { $noDb++; continue; }
    // an earlier include of inc/php8.php protects everything after it
    for ($i = 0; $i < $dbPos; $i++) {
        if (in_array($toks[$i][0], [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
            for ($j = $i + 1; $j < $n && !($toks[$j][0] === null && $toks[$j][1] === ';'); $j++) {
                if ($toks[$j][0] === T_CONSTANT_ENCAPSED_STRING && preg_match('~php8\.php~', $toks[$j][1])) { $dbPos = $i; break 2; }
            }
        }
    }
    $risky = [];
    for ($i = 0; $i < $dbPos; $i++) {
        $t = $toks[$i];
        if ($t[0] === T_VARIABLE && in_array($t[1], ['$_GET', '$_POST', '$_SESSION', '$_COOKIE', '$_SERVER', '$_REQUEST'], true)) $risky[] = "line {$t[2]}: {$t[1]}";
        if ($t[0] === T_STRING) {
            $k = php8_next_sig($toks, $i);
            if ($k >= 0 && $toks[$k][1] === '(' && !in_array(strtolower($t[1]), $skipOps, true)) $risky[] = "line {$t[2]}: {$t[1]}()";
        }
        if ($t[0] === T_VARIABLE && !in_array($t[1], ['$_GET', '$_POST', '$_SESSION', '$_COOKIE', '$_SERVER', '$_REQUEST'], true)) {
            // reading a plain variable (not the target of an assignment) can also warn, but only assignments of literals are common here
        }
    }
    if ($risky) $flagged[$rel] = array_slice(array_unique($risky), 0, 4); else $clean++;
}
echo count($flagged), " entry file(s) run code before inc/db.php; $clean include db.php with nothing risky before it; $noDb do not include inc/db.php directly\n";
foreach ($flagged as $rel => $r) echo "  $rel  (", implode('; ', $r), ")\n";
