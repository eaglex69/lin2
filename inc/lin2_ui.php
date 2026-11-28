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

/*
 * ---- markup of the menu / shop / inventory pages (css: theme/fantasy.css, section 10) -------------------------
 * Every function takes PLAIN text and plain URLs ('a.php?x=1&y=2'): it escapes them itself, so a caller must not
 * pass '&amp;' or html.  Painted pictures and glyphs are CSS classes (t-<name> = theme/fantasy/t-<name>.jpg,
 * g-<name> = theme/fantasy/g-<name>.png), so the pages work from any URL prefix and need no extra requests.
 */

/** «ГОРОД  /  Orc Village» */
function lin2_ui_place($kicker, $name)
{
    return '<div class="lx-place"><i>' . lin2_ui_h($kicker) . '</i><b>' . lin2_ui_h($name) . '</b></div>';
}

/**
 * One tile.  $icon: 'quest' = painted picture (t-quest), 'g-ring' = glyph.
 * $o: 'badge' (count in the red bubble, 0 / '' = none), 'cls' ('hot' red, 'vip' gold, 'zero' dimmed), 'sub' (small line, wide tiles)
 */
function lin2_ui_tile($href, $icon, $label, $o = array())
{
    $cls = 'lx-tile' . (!empty($o['cls']) ? ' ' . $o['cls'] : '');
    $ic = (strncmp($icon, 'g-', 2) === 0) ? $icon : 't-' . $icon;
    $h = '<a class="' . lin2_ui_h($cls) . '" href="' . lin2_ui_h($href) . '"><span class="ic ' . lin2_ui_h($ic) . '"></span>';
    if (isset($o['sub']) && $o['sub'] !== '') {
        $h .= '<span class="tx"><b>' . lin2_ui_h($label) . '</b><small>' . lin2_ui_h($o['sub']) . '</small></span>';
    } else {
        $h .= '<b>' . lin2_ui_h($label) . '</b>';
    }
    if (isset($o['badge']) && $o['badge'] !== '' && (int)$o['badge'] > 0) {
        $h .= '<i class="bd">' . (int)$o['badge'] . '</i>';
    }
    return $h . '</a>';
}

/** grid of tiles: array(array(href, icon, label[, options]), ...);  $cls: '' (3 per row), 'sm' (4 per row), 'big' (1 per row) */
function lin2_ui_tiles($tiles, $cls = '')
{
    $h = '<div class="lx-tiles' . ($cls !== '' ? ' ' . lin2_ui_h($cls) : '') . '">';
    foreach ($tiles as $t) {
        $h .= lin2_ui_tile($t[0], $t[1], $t[2], isset($t[3]) ? $t[3] : array());
    }
    return $h . '</div>';
}

/** plank buttons «where to go next»: array(array(href, label), ...) */
function lin2_ui_go($links)
{
    $h = '<div class="lx-go">';
    foreach ($links as $l) {
        $h .= '<a class="lx-go-a" href="' . lin2_ui_h($l[0]) . '">' . lin2_ui_h($l[1]) . '</a>';
    }
    return $h . '</div>';
}

/** sub-heading with a thin gold line: «ЭКИПИРОВКА   12 шт.» */
function lin2_ui_sub($title, $note = '')
{
    return '<div class="lx-sub">' . lin2_ui_h($title) . ($note !== '' ? '<em>' . lin2_ui_h($note) . '</em>' : '') . '</div>';
}

/** filter chips: array(array(label, href), ...), the item with href === null is the current one */
function lin2_ui_chips($title, $items)
{
    $h = '<div class="lx-chips">' . ($title !== '' ? '<b>' . lin2_ui_h($title) . '</b>' : '');
    foreach ($items as $it) {
        if ($it[1] === null) {
            $h .= '<span class="lx-chip on">' . lin2_ui_h($it[0]) . '</span>';
        } else {
            $h .= '<a class="lx-chip" href="' . lin2_ui_h($it[1]) . '">' . lin2_ui_h($it[0]) . '</a>';
        }
    }
    return $h . '</div>';
}

/** red tabs (sections of a shop): array(array(label, href), ...), href === null = the open tab */
function lin2_ui_tabs($items)
{
    $h = '<div class="lx-tabs">';
    foreach ($items as $it) {
        if ($it[1] === null) {
            $h .= '<span class="lx-tab on">' . lin2_ui_h($it[0]) . '</span>';
        } else {
            $h .= '<a class="lx-tab" href="' . lin2_ui_h($it[1]) . '">' . lin2_ui_h($it[0]) . '</a>';
        }
    }
    return $h . '</div>';
}

/** glossy button: $kind '' = green, 'red', 'grey'; $extra: 'sm', 'full' */
function lin2_ui_btn($href, $label, $kind = '', $extra = '')
{
    $cls = trim('lx-btn ' . $kind . ' ' . $extra);
    return '<a class="' . lin2_ui_h($cls) . '" href="' . lin2_ui_h($href) . '">' . lin2_ui_h($label) . '</a>';
}

/** grade letter of an item by its level (nlvl): NG D C B A S R */
function lin2_ui_grade($nlvl)
{
    $n = (int)$nlvl;
    if ($n < 20) {
        return 'NG';
    } elseif ($n < 40) {
        return 'D';
    } elseif ($n < 52) {
        return 'C';
    } elseif ($n < 62) {
        return 'B';
    } elseif ($n < 76) {
        return 'A';
    } elseif ($n < 91) {
        return 'S';
    }
    return 'R';
}

/** coloured grade mark: <i class="lx-gr gr-d">D</i> */
function lin2_ui_grade_mark($nlvl)
{
    $g = lin2_ui_grade($nlvl);
    return '<i class="lx-gr gr-' . strtolower($g) . '">' . $g . '</i>';
}

/** price pill with a coin */
function lin2_ui_price($n, $big = false, $no = false)
{
    return '<span class="lx-pr' . ($big ? ' big' : '') . ($no ? ' no' : '') . '">' . lin2_ui_num($n) . '</span>';
}

/** framed picture of an item: shmot/<name>.png on top of the glyph of its type (shows when the picture is missing) */
function lin2_ui_item_pic($name, $tip, $big = false)
{
    static $glyph = array(
        'weapon' => 'g-weapon', 'golova' => 'g-golova', 'body' => 'g-body', 'pants' => 'g-pants', 'nogi' => 'g-nogi',
        'ruki' => 'g-ruki', 'shit' => 'g-shit', 'plash' => 'g-plash', 'poyas' => 'g-earring', 'rpoyas' => 'g-earring',
        'amulet' => 'g-amulet', 'kolco' => 'g-ring', 'rkolco' => 'g-ring',
    );
    $g = isset($glyph[$tip]) ? $glyph[$tip] : 'g-bag';
    $file = explode('*', (string)$name);    // «Name*+3»: the picture is named after the part before the star
    $src = '/shmot/' . rawurlencode(trim($file[0])) . '.png';
    return '<span class="lx-ic ' . $g . ($big ? ' big' : '') . '"><img src="' . lin2_ui_h($src) . '" alt=""/></span>';
}

/** list of «label  value» lines: array(array(label, value), ...) ; $cls 'lx-st' (card) or 'lx-rows' (sheet) */
function lin2_ui_stats($rows, $cls = 'lx-st')
{
    $h = '<div class="' . $cls . '">';
    foreach ($rows as $r) {
        $h .= '<span>' . lin2_ui_h($r[0]) . '<b>' . lin2_ui_h($r[1]) . '</b></span>';
    }
    return $h . '</div>';
}

/** stat lines of an equipment row (shop / item): weapon = attack, everything else = defence */
function lin2_ui_item_stats($row)
{
    $g = function ($k) use ($row) {
        return number_format(isset($row[$k]) ? (float)$row[$k] : 0, 0, ',', "\xC2\xA0");   // non-breaking space
    };
    if (isset($row['tip']) && $row['tip'] === 'weapon') {
        return array(array('Физ. атака', $g('patt')), array('Маг. атака', $g('matt')));
    }
    return array(array('Физ. защ.', $g('pdef')), array('Маг. защ.', $g('mdef')));
}

/** product card of a shop (2 per row): $row = row of `shop`, $info = url of the info page, $buy = url of the purchase,
 *  $afford = false paints the price red and the button grey (the purchase page still explains what is missing) */
function lin2_ui_good($row, $info, $buy, $afford = true)
{
    $name = isset($row['name']) ? (string)$row['name'] : '';
    $h = '<div class="lx-gd">' . lin2_ui_grade_mark(isset($row['nlvl']) ? $row['nlvl'] : 0);
    $h .= lin2_ui_item_pic($name, isset($row['tip']) ? $row['tip'] : '');
    $h .= '<a class="nm" href="' . lin2_ui_h($info) . '">' . lin2_ui_h($name) . '</a>';
    $h .= lin2_ui_stats(lin2_ui_item_stats($row));
    $h .= lin2_ui_price(isset($row['cena']) ? $row['cena'] : 0, false, !$afford);
    $h .= lin2_ui_btn($buy, 'Купить', $afford ? '' : 'grey');
    return $h . '</div>';
}

/** grid of product cards: $rows = rows of `shop`; $info_base / $buy_base = urls that end with 'id=' */
function lin2_ui_goods($rows, $info_base, $buy_base, $money = null)
{
    if (!$rows) {
        return lin2_ui_empty('В этом грейде пока ничего нет');
    }
    $h = '<div class="lx-goods">';
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $h .= lin2_ui_good($r, $info_base . $id, $buy_base . $id, $money === null || (float)$money >= (float)$r['cena']);
    }
    return $h . '</div>';
}

/** grade filter: $base = url that ends with 'lvlus=', $cur = current level, $max = the highest grade of this shop (91 = R) */
function lin2_ui_grade_chips($base, $cur, $max = 91)
{
    $all = array(0 => 'NG', 20 => 'D', 40 => 'C', 52 => 'B', 62 => 'A', 76 => 'S', 91 => 'R');
    $items = array();
    foreach ($all as $lv => $g) {
        if ($lv <= $max) {
            $items[] = array($g, ((int)$cur === $lv) ? null : $base . $lv);
        }
    }
    return lin2_ui_chips('Грейд', $items);
}

/** the three shops as red tabs: $active = 'weapon' | 'b' | 'jewel' */
function lin2_ui_shop_tabs($active)
{
    $t = array('weapon' => array('Оружие', 'shop.php?mod=weapon'), 'b' => array('Броня', 'shop.php?mod=b'), 'jewel' => array('Украшения', 'shopjuvilir.php?'));
    $items = array();
    foreach ($t as $k => $v) {
        $items[] = array($v[0], $k === $active ? null : $v[1]);
    }
    return lin2_ui_tabs($items);
}

/** name of an equipment type: 'body' -> «Доспехи»; $plural = the name of the whole group («Шлемы») */
function lin2_ui_tip_name($tip, $plural = false)
{
    static $one = array(
        'weapon' => 'Оружие', 'body' => 'Доспехи', 'golova' => 'Шлем', 'nogi' => 'Сапоги', 'shit' => 'Щит', 'poyas' => 'Левая серьга',
        'rpoyas' => 'Правая серьга', 'plash' => 'Плащ', 'pants' => 'Штаны', 'ruki' => 'Рукавицы', 'kolco' => 'Левое кольцо',
        'rkolco' => 'Правое кольцо', 'amulet' => 'Амулет',
    );
    static $many = array(
        'weapon' => 'Оружие', 'body' => 'Доспехи', 'golova' => 'Шлемы', 'nogi' => 'Сапоги', 'shit' => 'Щиты', 'poyas' => 'Левые серьги',
        'rpoyas' => 'Правые серьги', 'plash' => 'Плащи', 'pants' => 'Штаны', 'ruki' => 'Рукавицы', 'kolco' => 'Левые кольца',
        'rkolco' => 'Правые кольца', 'amulet' => 'Амулеты',
    );
    $a = $plural ? $many : $one;
    return isset($a[$tip]) ? $a[$tip] : '';
}

/** kind of a weapon: 'me4' -> «Меч» */
function lin2_ui_weapon_kind($k)
{
    static $kinds = array(
        'ydar' => 'Ударное', 'luk' => 'Лук', 'kin' => 'Кинжал', 'sdv' => 'Сдвоенное', 'kas' => 'Кастет', 'kniga' => 'Книга',
        'koppik' => 'Копьё/Пика', 'me4' => 'Меч', 'rap' => 'Рапира',
    );
    return isset($kinds[$k]) ? $kinds[$k] : '';
}

/** parchment sheet: $pic = html of the picture, $mark = html of the grade mark, $rows = stat lines, $acts = array(array(href, label, kind)) */
function lin2_ui_sheet($title, $sub, $rows, $acts = array(), $pic = '', $mark = '')
{
    $h = '<div class="lx-sheet">' . $pic . '<h3>' . lin2_ui_h($title) . $mark . '</h3>';
    if ($sub !== '') {
        $h .= '<p class="sub">' . lin2_ui_h($sub) . '</p>';
    }
    if ($rows) {
        $h .= lin2_ui_stats($rows, 'lx-rows');
    }
    return $h . lin2_ui_acts($acts) . '</div>';
}

/** row of buttons: array(array(href, label[, kind]), ...) */
function lin2_ui_acts($acts, $size = '')
{
    if (!$acts) {
        return '';
    }
    $h = '<div class="lx-acts2">';
    foreach ($acts as $a) {
        $h .= lin2_ui_btn($a[0], $a[1], isset($a[2]) ? $a[2] : '', $size);
    }
    return $h . '</div>';
}

/**
 * Info sheet of one item (row of `shop` or `item`).
 * $o: 'price' (number: shows the price pill), 'extra' (more lines array(array(label, value))), 'acts' (buttons)
 */
function lin2_ui_item_sheet($row, $o = array())
{
    $tip = isset($row['tip']) ? $row['tip'] : '';
    $nl = isset($row['nlvl']) ? (int)$row['nlvl'] : 0;
    $sub = lin2_ui_tip_name($tip);
    $kind = !empty($row['ruka']) ? lin2_ui_weapon_kind($row['ruka']) : (!empty($row['tip2']) ? lin2_ui_weapon_kind($row['tip2']) : '');   // item.ruka / shop.tip2
    if ($kind !== '' && $tip === 'weapon') {
        $sub .= ' · ' . $kind;
    }
    $rows = lin2_ui_item_stats($row);
    if ($tip === 'weapon') {
        $rows[] = array('SoulShot', 'x' . (isset($row['soul']) ? (int)$row['soul'] : 0));
        $rows[] = array('SpiritShot', 'x' . (isset($row['spirit']) ? (int)$row['spirit'] : 0));
    }
    $rows[] = array('Уровень', $nl);
    $rows[] = array('Грейд', lin2_ui_grade($nl));
    if (!empty($o['extra'])) {
        $rows = array_merge($rows, $o['extra']);
    }
    $h = '<div class="lx-sheet">' . lin2_ui_item_pic(isset($row['name']) ? $row['name'] : '', $tip, true);
    $h .= '<h3>' . lin2_ui_h(isset($row['name']) ? $row['name'] : '') . lin2_ui_grade_mark($nl) . '</h3>';
    $h .= '<p class="sub">' . lin2_ui_h($sub) . '</p>' . lin2_ui_stats($rows, 'lx-rows');
    if (isset($o['price'])) {
        $h .= lin2_ui_price($o['price'], true);
    }
    return $h . lin2_ui_acts(isset($o['acts']) ? $o['acts'] : array()) . '</div>';
}

/** inventory row of an item: picture, name (-> info), grade, stats, buttons: $acts = array(array(href, label[, kind]), ...) */
function lin2_ui_item_row($row, $info, $acts = array())
{
    $name = isset($row['name']) ? (string)$row['name'] : '';
    $tip = isset($row['tip']) ? $row['tip'] : '';
    $h = '<div class="lx-it">' . lin2_ui_item_pic($name, $tip) . '<div class="bd">';
    $h .= '<a class="nm" href="' . lin2_ui_h($info) . '">' . lin2_ui_h($name) . '</a>' . lin2_ui_grade_mark(isset($row['nlvl']) ? $row['nlvl'] : 0);
    $kind = ($tip === 'weapon' && !empty($row['ruka'])) ? lin2_ui_weapon_kind($row['ruka']) : '';
    if ($kind !== '') {
        $h .= '<span class="sub">' . lin2_ui_h($kind) . '</span>';
    }
    $h .= lin2_ui_stats(lin2_ui_item_stats($row));
    return $h . lin2_ui_acts($acts, 'sm') . '</div></div>';
}

/**
 * Row of a resource / elixir / scroll / pet thing.  $glyph: class of the picture (g-ore), $pic: url of the picture or '',
 * $sub: small line («12 шт.»), $lead: html in front of the picture (radio button)
 */
function lin2_ui_thing_row($name, $href, $glyph, $pic, $sub, $acts = array(), $lead = '')
{
    $ic = '<span class="lx-ic ' . lin2_ui_h($glyph) . '">' . ($pic !== '' ? '<img src="' . lin2_ui_h($pic) . '" alt=""/>' : '') . '</span>';
    $nm = $href !== '' ? '<a class="nm" href="' . lin2_ui_h($href) . '">' . lin2_ui_h($name) . '</a>' : '<b class="nm">' . lin2_ui_h($name) . '</b>';
    $tx = '<div class="tx">' . $nm . ($sub !== '' ? '<span class="sub">' . lin2_ui_h($sub) . '</span>' : '') . '</div>';
    return '<div class="lx-it rw">' . $lead . $ic . '<div class="bd">' . $tx . lin2_ui_acts($acts, 'sm') . '</div></div>';
}

/** question card «Выкинуть X?» with two buttons */
function lin2_ui_confirm($question, $yes, $no)
{
    return '<div class="lx-sheet"><h3>' . lin2_ui_h($question) . '</h3>' . lin2_ui_acts(array(array($yes, 'Да', 'red'), array($no, 'Нет', 'grey'))) . '</div>';
}

/** one-line message in a frame (saved, thrown away ...) */
function lin2_ui_note($html)
{
    return '<div class="lx-hint">' . $html . '</div>';
}

/** «nothing here» plate */
function lin2_ui_empty($text)
{
    return '<div class="lx-empty">' . lin2_ui_h($text) . '</div>';
}

/** page numbers: [1] 2 3 ...  $base = url that ends with 'page=' */
function lin2_ui_pager($page, $pages, $base)
{
    if ($pages < 2) {
        return '';
    }
    $h = '<div class="lx-pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $h .= ($i == $page) ? '<b>' . $i . '</b>' : '<a href="' . lin2_ui_h($base . $i) . '">' . $i . '</a>';
    }
    return $h . '</div>';
}

/**
 * Panel with a title (every piece of information on the new pages lives in one): $title = plain text, $body = html,
 * $cls = '' (copper leather) | 'in' (dark recessed), add 'kv' when the body is made of stat rows (no padding), $note = plain text at the right of the title.
 */
function lin2_ui_panel($title, $body, $cls = '', $note = '')
{
    return '<div class="lx-pn' . ($cls !== '' ? ' ' . lin2_ui_h($cls) : '') . '"><div class="lx-pt">' . lin2_ui_h($title)
        . ($note !== '' ? '<em>' . lin2_ui_h($note) . '</em>' : '') . '</div><div class="lx-pb">' . $body . '</div></div>';
}

/**
 * Stat rows (label on the left, value on the right): array(array($icon, $label, $valueHtml[, $bonusText[, $plusHref[, $plusLabel]]]), ...)
 * $icon: '' | att def hp mp coin gem bar xp st win lose (small picture before the label); $label and $bonusText are plain text,
 * $valueHtml is html (the caller escapes what comes from the database); $plusHref = small gold «+» button at the end of the row.
 * $g2 = two columns.
 */
function lin2_ui_kv($rows, $g2 = false)
{
    $h = '<div class="lx-kv' . ($g2 ? ' g2' : '') . '">';
    foreach ($rows as $r) {
        $ic = isset($r[0]) ? (string)$r[0] : '';
        $h .= '<div class="r' . ($ic !== '' ? ' ' . lin2_ui_h($ic) : '') . '">' . ($ic !== '' ? '<u></u>' : '') . '<i>' . lin2_ui_h($r[1]) . '</i><b>' . $r[2];
        if (isset($r[3]) && $r[3] !== '') {
            $h .= '<em>' . lin2_ui_h($r[3]) . '</em>';
        }
        $h .= '</b>';
        if (!empty($r[4])) {
            $h .= '<a class="lx-plus" href="' . lin2_ui_h($r[4]) . '">' . lin2_ui_h(isset($r[5]) ? $r[5] : '+') . '</a>';
        }
        $h .= '</div>';
    }
    return $h . '</div>';
}

/** bar with the numbers inside (HP / MP / EXP), optionally with a gold «+» button: $cls = 'lx-hp' | 'lx-mp' | 'lx-xp' */
function lin2_ui_meter($cls, $text, $cur, $max, $plusHref = '', $plusLabel = '+')
{
    $h = '<div class="lx-mt"><div class="lx-bar ' . lin2_ui_h($cls) . '"><i style="width:' . lin2_ui_pct($cur, $max) . '%"></i><b>' . lin2_ui_h($text) . '</b></div>';
    if ($plusHref !== '') {
        $h .= '<a class="lx-plus" href="' . lin2_ui_h($plusHref) . '">' . lin2_ui_h($plusLabel) . '</a>';
    }
    return $h . '</div>';
}

/** worn items of a player: array(tip => row of `item`), one query; a slot that holds nothing is missing from the array */
function lin2_ui_worn($usr)
{
    $out = array();
    $u = function_exists('mysql_real_escape_string') ? mysql_real_escape_string((string)$usr) : addslashes((string)$usr);
    $q = mysql_query("SELECT * FROM `item` WHERE `usr` = '" . $u . "' and `image` = 'yes' ORDER BY `id`");
    if ($q) {
        while ($r = mysql_fetch_array($q)) {
            if (!isset($out[$r['tip']])) {
                $out[$r['tip']] = $r;
            }
        }
    }
    return $out;
}

/**
 * One slot of the doll.  $row = row of `item` or null (empty slot).
 * $o: 'info' = url that ends with 'id=' (page of the item), 'take' = url that ends with 'tip=' (take off: the red cross), '' = no cross,
 *     'bag' = url that ends with 'o=' (an empty slot opens that part of the bag), '' = not a link.
 */
function lin2_ui_slot($tip, $row, $o)
{
    $name = lin2_ui_tip_name($tip);
    if ($row) {
        $h = '<div class="lx-sl on s-' . lin2_ui_h($tip) . '"><a class="lx-slot" href="' . lin2_ui_h($o['info'] . (int)$row['id']) . '" title="' . lin2_ui_h($name . ': ' . $row['name']) . '">'
            . lin2_ui_item_pic($row['name'], $tip) . '</a>' . lin2_ui_grade_mark(isset($row['nlvl']) ? $row['nlvl'] : 0);
        if ($o['take'] !== '') {
            $h .= '<a class="lx-un" href="' . lin2_ui_h($o['take'] . $tip) . '" title="Снять">&times;</a>';
        }
        return $h . '</div>';
    }
    $pic = lin2_ui_item_pic_empty($tip);
    if ($o['bag'] !== '') {
        return '<div class="lx-sl empty s-' . lin2_ui_h($tip) . '"><a class="lx-slot" href="' . lin2_ui_h($o['bag'] . $tip) . '" title="' . lin2_ui_h($name . ': пусто') . '">' . $pic . '</a></div>';
    }
    return '<div class="lx-sl empty s-' . lin2_ui_h($tip) . '"><span class="lx-slot" title="' . lin2_ui_h($name . ': пусто') . '">' . $pic . '</span></div>';
}

/** only the glyph of an equipment type, in an item frame (no picture file is asked for) */
function lin2_ui_item_pic_empty($tip)
{
    static $glyph = array(
        'weapon' => 'g-weapon', 'golova' => 'g-golova', 'body' => 'g-body', 'pants' => 'g-pants', 'nogi' => 'g-nogi',
        'ruki' => 'g-ruki', 'shit' => 'g-shit', 'plash' => 'g-plash', 'poyas' => 'g-earring', 'rpoyas' => 'g-earring',
        'amulet' => 'g-amulet', 'kolco' => 'g-ring', 'rkolco' => 'g-ring',
    );
    return '<span class="lx-ic ' . (isset($glyph[$tip]) ? $glyph[$tip] : 'g-bag') . '"></span>';
}

/**
 * The doll: the character in the middle, worn items on both sides (weapon on top, six slots on the left, six on the right).
 * $worn = lin2_ui_worn(...).  $o: 'img' = picture of the character, 'rank' = text under the figure,
 * 'info' (default 'inventar.php?mod=info&id='), 'take' (url + 'tip=' of the take-off link or ''), 'bag' (url + 'o=' or '').
 */
function lin2_ui_doll($worn, $o = array())
{
    $o = $o + array('img' => '', 'rank' => '', 'info' => 'inventar.php?mod=info&id=', 'take' => '', 'bag' => '');
    $left = array('golova', 'body', 'pants', 'ruki', 'nogi', 'plash');
    $right = array('shit', 'amulet', 'poyas', 'rpoyas', 'kolco', 'rkolco');
    $h = '<div class="lx-doll"><div class="lx-dtop">' . lin2_ui_slot('weapon', isset($worn['weapon']) ? $worn['weapon'] : null, $o) . '</div><div class="lx-dcol">';
    foreach ($left as $t) {
        $h .= lin2_ui_slot($t, isset($worn[$t]) ? $worn[$t] : null, $o);
    }
    $h .= '</div><div class="lx-fig">' . ($o['img'] !== '' ? '<img src="' . lin2_ui_h($o['img']) . '" alt=""/>' : '')
        . ($o['rank'] !== '' ? '<span class="rk">' . lin2_ui_h($o['rank']) . '</span>' : '') . '</div><div class="lx-dcol">';
    foreach ($right as $t) {
        $h .= lin2_ui_slot($t, isset($worn[$t]) ? $worn[$t] : null, $o);
    }
    return $h . '</div></div>';
}

/** «3 Дн. 4 Ч. 5 М. 6 С.» for a number of seconds (timers of the character page) */
function lin2_ui_left($sec)
{
    $sec = max(0, (int)$sec);
    $d = (int)floor($sec / 86400);
    $h = (int)floor(($sec % 86400) / 3600);
    $m = (int)floor(($sec % 3600) / 60);
    $s = $sec % 60;
    return $d . ' Дн. ' . $h . ' Ч. ' . $m . ' М. ' . $s . ' С.';
}

}
