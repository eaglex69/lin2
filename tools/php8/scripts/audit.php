<?php
// Usage (inside php-wasm):  php audit.php /work  >  report.json
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/lib.php';

$root  = rtrim($argv[1] ?? '/work', '/');
$files = php8_list_files($root);
$known = php8_known_constants();
$internalFns = array_flip(array_map('strtolower', get_defined_functions()['internal']));

// ---------- pass 1: project-defined functions / constants / classes ----------
$projFns = []; $projConsts = []; $projClasses = []; $perFile = [];
foreach ($files as $f) {
    $code = file_get_contents($f);
    $toks = php8_tokens($code);
    $perFile[$f] = $toks;
    $n = count($toks);
    for ($i = 0; $i < $n; $i++) {
        $id = $toks[$i][0];
        if ($id === T_FUNCTION) {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][1] === '&') $j = php8_next_sig($toks, $j);
            if ($j >= 0 && $toks[$j][0] === T_STRING) $projFns[strtolower($toks[$j][1])] = $f;
        } elseif ($id === T_CLASS || $id === T_INTERFACE || $id === T_TRAIT) {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][0] === T_STRING) $projClasses[strtolower($toks[$j][1])] = $f;
        } elseif ($id === T_STRING && strtolower($toks[$i][1]) === 'define') {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][1] === '(') {
                $k = php8_next_sig($toks, $j);
                if ($k >= 0 && $toks[$k][0] === T_CONSTANT_ENCAPSED_STRING) $projConsts[trim($toks[$k][1], "'\"")] = $f;
            }
        } elseif ($id === T_CONST) {
            $j = php8_next_sig($toks, $i);
            if ($j >= 0 && $toks[$j][0] === T_STRING) $projConsts[$toks[$j][1]] = $f;
        }
    }
}

$out = ['files' => count($files), 'consts' => [], 'unknown_calls' => [], 'unknown_classes' => [], 'shorttags' => [], 'ctor4' => [], 'watch' => [], 'ops' => [], 'bom' => [], 'crlf' => 0];
$watch = array_flip(PHP8_WATCH);
foreach ($files as $f) {
    $rel = ltrim(substr($f, strlen($root)), '/');
    $code = file_get_contents($f);
    if (substr($code, 0, 3) === "\xEF\xBB\xBF") $out['bom'][] = $rel;
    if (strpos($code, "\r\n") !== false) $out['crlf']++;
    $toks = $perFile[$f];
    $ctxs = php8_ctx_map($toks);
    $cls = php8_classify($toks);
    foreach ($cls as $r) {
        $lname = strtolower($r['name']);
        if ($r['kind'] === 'const') {
            if (isset($known[$r['name']]) || isset($projConsts[$r['name']])) continue;
            $out['consts'][] = [$rel, $r['line'], $r['sub'], $r['ctx'], $r['name'], php8_line_text($code, $r['line'])];
        } elseif ($r['kind'] === 'call') {
            if (isset($internalFns[$lname]) || isset($projFns[$lname])) {
                if (isset($watch[$lname])) $out['watch'][] = [$rel, $r['line'], $lname, php8_line_text($code, $r['line'])];
                continue;
            }
            $out['unknown_calls'][] = [$rel, $r['line'], $r['name'], php8_line_text($code, $r['line'])];
        } elseif ($r['kind'] === 'newclass' || $r['kind'] === 'staticclass') {
            if (in_array($lname, ['self', 'parent', 'static'], true)) continue;
            if (class_exists($r['name'], false) || interface_exists($r['name'], false) || isset($projClasses[$lname])) continue;
            $out['unknown_classes'][] = [$rel, $r['line'], $r['name'], php8_line_text($code, $r['line'])];
        }
    }
    // short tags, / and % operators
    foreach ($toks as $i => [$id, $text, $line]) {
        if ($id === T_OPEN_TAG && strncasecmp($text, '<?php', 5) !== 0) $out['shorttags'][$rel] = ($out['shorttags'][$rel] ?? 0) + 1;
        if ($ctxs[$i] === 'dq') continue;
        $isOp = ($id === null && ($text === '/' || $text === '%')) || $id === T_DIV_EQUAL || $id === T_MOD_EQUAL;
        if ($isOp) {
            $nx = php8_next_sig($toks, $i);
            $nt = $nx >= 0 ? $toks[$nx] : [null, ''];
            $lit = ($nt[0] === T_LNUMBER && (int)$nt[1] !== 0) || ($nt[0] === T_DNUMBER && (float)$nt[1] != 0.0);
            $out['ops'][] = [$rel, $line, $text, $lit ? 'literal' : 'dynamic', php8_line_text($code, $line)];
        }
    }
    // PHP4 constructors
    for ($i = 0, $n = count($toks); $i < $n; $i++) {
        if ($toks[$i][0] !== T_CLASS) continue;
        $j = php8_next_sig($toks, $i); if ($j < 0 || $toks[$j][0] !== T_STRING) continue;
        $cname = strtolower($toks[$j][1]);
        // find class body braces
        $k = $j; while ($k < $n && $toks[$k][1] !== '{') $k++;
        $d = 0; $has4 = false; $hasNew = false;
        for (; $k < $n; $k++) {
            if ($toks[$k][1] === '{' || $toks[$k][0] === T_CURLY_OPEN) $d++;
            elseif ($toks[$k][1] === '}') { $d--; if ($d === 0) break; }
            if ($d === 1 && $toks[$k][0] === T_FUNCTION) {
                $m = php8_next_sig($toks, $k);
                if ($m >= 0 && $toks[$m][0] === T_STRING) {
                    $ml = strtolower($toks[$m][1]);
                    if ($ml === $cname) $has4 = true;
                    if ($ml === '__construct') $hasNew = true;
                }
            }
        }
        if ($has4) $out['ctor4'][] = [$rel, $toks[$j][1], $hasNew];
    }
}
$out['projFns'] = count($projFns); $out['projConsts'] = array_keys($projConsts);
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
