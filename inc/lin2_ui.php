<?php
/*
 * Helpers of the "fantasy" theme (theme/fantasy.css, theme/ajax.js, theme/battle.js).
 * Only builds markup / picks pictures; no game logic lives here.
 *
 * Pictures (all optional, the theme falls back to the bundled art in theme/fantasy/):
 *   foto/<nick>.gif                      player photo (as before)
 *   pic/avatar/<side>/<m|f>/<sex>/<rank>.png   class art of a player (as before)
 *   pic/mobs/<mob id>.jpg|png|webp|gif   portrait of ONE mob (new): drop a file there and the battle card uses it
 */
defined('PROTECTOR') or die('Error: restricted access');

if (!function_exists('lin2_ui_root')) {

/** game root directory (this file lives in <root>/inc) */
function lin2_ui_root()
{
    return dirname(dirname(__FILE__));
}

/** '/theme/<file>' + ?v=<mtime>, so browsers pick up a changed file at once */
function lin2_ui_asset($file)
{
    $f = lin2_ui_root() . '/theme/' . $file;
    $v = is_file($f) ? @filemtime($f) : 0;
    return '/theme/' . $file . ($v ? '?v=' . $v : '');
}

function lin2_ui_h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 1234567 -> "1 234 567" (non-breaking spaces) */
function lin2_ui_num($n)
{
    return str_replace(' ', '&nbsp;', number_format((float)$n, 0, ',', ' '));
}

/** 0..100 */
function lin2_ui_pct($v, $max)
{
    $max = (float)$max;
    if ($max <= 0) {
        return 0;
    }
    $p = (float)$v * 100 / $max;
    if ($p < 0) {
        $p = 0;
    }
    if ($p > 100) {
        $p = 100;
    }
    return round($p, 2);
}

function lin2_ui_safe_name($s)
{
    return $s !== '' && strpbrk($s, "/\\\0") === false && strpos($s, '..') === false;
}

/** picture of a player: photo, class art, or the bundled hero portrait ($card = tall picture for a battle card) */
function lin2_ui_user_img($u, $card = false)
{
    $root = lin2_ui_root();
    $nick = isset($u['usr']) ? (string)$u['usr'] : '';
    if (lin2_ui_safe_name($nick) && is_file($root . '/foto/' . $nick . '.gif')) {
        return '/foto/' . rawurlencode($nick) . '.gif';
    }
    $lvl = isset($u['lvl']) ? (int)$u['lvl'] : 1;
    if ($lvl < 20) {
        $rank = 'NG';
    } elseif ($lvl < 40) {
        $rank = 'D';
    } elseif ($lvl < 52) {
        $rank = 'C';
    } elseif ($lvl < 62) {
        $rank = 'B';
    } elseif ($lvl < 76) {
        $rank = 'A';
    } else {
        $rank = 'S';
    }
    $mage = isset($u['klas']) && $u['klas'] == 'wizard';
    $kl = $mage ? 'm' : 'f';
    $side = isset($u['storona']) ? (string)$u['storona'] : '';
    $sex = isset($u['pol']) ? (string)$u['pol'] : '';
    if (preg_match('/^[A-Za-z0-9_]+$/', $side) && preg_match('/^[A-Za-z0-9_]+$/', $sex)) {
        $rel = 'pic/avatar/' . $side . '/' . $kl . '/' . $sex . '/' . $rank . '.png';
        if (is_file($root . '/' . $rel)) {
            return '/' . $rel;
        }
    }
    return lin2_ui_asset(($card ? 'fantasy/card-' : 'fantasy/hero-') . ($mage ? 'mage' : 'warrior') . '.jpg');
}

/** small round avatar block (header of every page) */
function lin2_ui_avatar($u)
{
    return '<div class="lx-ava"><img src="' . lin2_ui_h(lin2_ui_user_img($u)) . '" alt=""/></div>';
}

/** array(card picture, round avatar) of a mob */
function lin2_ui_mob_art($mob)
{
    $root = lin2_ui_root();
    $id = isset($mob['id']) ? (int)$mob['id'] : 0;
    if ($id > 0) {
        $exts = array('jpg', 'png', 'webp', 'gif', 'jpeg');
        foreach ($exts as $e) {
            if (is_file($root . '/pic/mobs/' . $id . '.' . $e)) {
                $p = '/pic/mobs/' . $id . '.' . $e;
                return array($p, $p);
            }
        }
    }
    // ASCII-only lower case on purpose: strtolower() depends on the locale / PHP version, the picture of a mob must not change
    $name = isset($mob['name']) ? strtr((string)$mob['name'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') : '';
    $v = abs(crc32($name)) % 4;
    return array(lin2_ui_asset('fantasy/mob' . $v . '.jpg'), lin2_ui_asset('fantasy/mava' . $v . '.jpg'));
}

/**
 * Battle panel: avatar with level, HP bar (yellow "trail" + fill), attack / defence / HP numbers.
 * $cls: 'lx-foe' or 'lx-me'.  data-* attributes are what theme/battle.js animates.
 */
function lin2_ui_side($cls, $id, $name, $lvl, $img, $att, $def, $hp, $hpall)
{
    $hp = max(0, (int)$hp);
    $hpall = max(1, (int)$hpall);
    $pct = lin2_ui_pct($hp, $hpall);
    return '<div class="lx-side ' . $cls . '" id="' . $id . '" data-name="' . lin2_ui_h($name) . '" data-hp="' . $hp . '" data-max="' . $hpall . '">'
        . '<div class="lx-ava"><img src="' . lin2_ui_h($img) . '" alt=""/><b>' . (int)$lvl . '</b></div>'
        . '<div class="lx-sd"><div class="lx-hpbar"><i class="trail" style="width:' . $pct . '%"></i><i class="fill" style="width:' . $pct . '%"></i></div>'
        . '<div class="lx-stats"><span class="lx-st-att"><u></u>' . lin2_ui_num($att) . '</span>'
        . '<span class="lx-st-def"><u></u>' . lin2_ui_num($def) . '</span>'
        . '<span class="lx-st-hp"><u></u><em class="lx-hpnum">' . lin2_ui_num($hp) . '</em></span></div></div></div>';
}

/** Portrait card with the "your turn" caption, the attack button is printed by the caller */
function lin2_ui_card($img, $alt)
{
    return '<div class="lx-card" id="lx-card"><div class="lx-art"><img src="' . lin2_ui_h($img) . '" alt="' . lin2_ui_h($alt) . '"/></div>'
        . '<div class="lx-turn" id="lx-turn">ВАШ ХОД</div><div class="lx-flash"></div><div class="lx-slash"></div></div>';
}

/**
 * Screen of a turn based fight (pk.php, combat.php, fight.php): enemy panel, portrait card, round "attack" button,
 * own panel and the two pickers "hit" / "block" - everything inside ONE form (POST), so it works without JavaScript.
 *   action  form action ('pk.php?mod=ataka')        key    string, the animation is carried over between pages with the same key
 *   foe     row (name|usr, lvl, hp, hpall, patt, matt, pdef, mdef[, klas])      art  array(card picture, avatar) or null (= player art)
 *   me      row of the player ($udata)              magic  extra <option>s of the "hit" list (castle fights), '' = none
 */
function lin2_ui_duel($o)
{
    $foe = $o['foe'];
    $me = $o['me'];
    $wm = (isset($me['klas']) && $me['klas'] == 'wizard');
    $wf = isset($foe['klas']) ? ($foe['klas'] == 'wizard') : $wm;
    $fname = isset($foe['usr']) ? $foe['usr'] : (isset($foe['name']) ? $foe['name'] : '');
    $art = (isset($o['art']) && is_array($o['art'])) ? $o['art'] : array(lin2_ui_user_img($foe, true), lin2_ui_user_img($foe));
    $zones = array(1 => 'Голова', 2 => 'Грудь', 3 => 'Ноги');
    $opts = '';
    foreach ($zones as $v => $t) {
        $opts .= '<option value="' . $v . '">' . $t . '</option>';
    }
    $magic = isset($o['magic']) ? $o['magic'] : '';
    $get = function ($r, $k) {
        return isset($r[$k]) ? $r[$k] : 0;
    };
    $h = '<div class="lx-bt lx-duel" id="lx-bt" data-foe="' . lin2_ui_h(isset($o['key']) ? $o['key'] : $fname) . '">'
        . '<form action="' . lin2_ui_h($o['action']) . '" method="post">';
    $h .= lin2_ui_side('lx-foe', 'lx-foe', $fname, $get($foe, 'lvl'), $art[1], $wf ? $get($foe, 'matt') : $get($foe, 'patt'), $wf ? $get($foe, 'mdef') : $get($foe, 'pdef'), $get($foe, 'hp'), $get($foe, 'hpall'));
    $h .= '<div class="lx-name">' . lin2_ui_h($fname) . ' <small>[' . (int)$get($foe, 'lvl') . ' ур.]</small></div>';
    $h .= lin2_ui_card($art[0], $fname);
    $h .= '<button type="submit" class="lx-atk" id="lx-atk" title="Атаковать"><span>Атаковать</span></button>';
    $mname = isset($me['usr']) ? $me['usr'] : '';
    $h .= lin2_ui_side('lx-me', 'lx-me', $mname, $get($me, 'lvl'), lin2_ui_user_img($me), $wm ? $get($me, 'matt') : $get($me, 'patt'), $wm ? $get($me, 'mdef') : $get($me, 'pdef'), $get($me, 'hp'), $get($me, 'hpall'));
    $h .= '<div class="lx-pick"><label><span>Удар</span><select name="udar">' . $opts . $magic . '</select></label>'
        . '<label><span>Блок</span><select name="block">' . $opts . '</select></label></div>';
    $h .= '</form></div>';
    return $h;
}

}
