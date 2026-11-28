#!/usr/bin/env python3
"""
Draws the glyph icons of the "fantasy" theme: theme/fantasy/g-<name>.svg  (equipment slots, shop sections, footer keys).

The glyphs are plain SVG made of a few paths (no external resources, no fonts); gradients that a glyph does not use are
left out of its file, so every file is about 1 KB.  Usage:  python3 tools/php8/art/make_glyphs.py [outdir]
"""
import os
import re
import sys

OUT = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', '..', 'theme', 'fantasy')
INK = '#2b1405'

DEFS = {
    'g': ('0', '1', [('0', '#fff3b8'), ('.5', '#f3b93f'), ('1', '#a8601a')]),     # gold
    's': ('0', '1', [('0', '#f6f9fc'), ('.5', '#b2bcc7'), ('1', '#6a7480')]),     # steel (diagonal below)
    'l': ('0', '1', [('0', '#c27c42'), ('1', '#6b3515')]),                        # leather
    'r': ('0', '1', [('0', '#ff836a'), ('.55', '#d8301f'), ('1', '#8c140c')]),    # red cloth / ruby
    'b': ('0', '1', [('0', '#c8f4ff'), ('.5', '#42b2f0'), ('1', '#1a54b8')]),     # blue gem
    'p': ('0', '1', [('0', '#fff7da'), ('1', '#e3c07a')]),                        # parchment
    'n': ('0', '1', [('0', '#a9b0b8'), ('1', '#5f666e')]),                        # stone
    'v': ('0', '1', [('0', '#8be07a'), ('1', '#2c8a3a')]),                        # green
    'w': ('0', '1', [('0', '#8fd4ff'), ('1', '#8fd4ff')]),                        # soft blue glow (radial, see defs_for)
}


def defs_for(body):
    used = sorted(set(re.findall(r'url\(#(\w)\)', body)))
    out = []
    for k in used:
        _, _, stops = DEFS[k]
        if k == 'w':
            out.append('<radialGradient id="w"><stop offset="0" stop-color="#8fd4ff" stop-opacity=".75"/>'
                       '<stop offset=".6" stop-color="#4aa8ff" stop-opacity=".3"/><stop offset="1" stop-color="#4aa8ff" stop-opacity="0"/></radialGradient>')
            continue
        diag = ' x2="1"' if k == 's' else ' x2="0"'
        out.append('<linearGradient id="%s" x1="0" y1="0"%s y2="1">%s</linearGradient>' % (
            k, diag, ''.join('<stop offset="%s" stop-color="%s"/>' % st for st in stops)))
    return '<defs>%s</defs>' % ''.join(out) if out else ''


def svg(body, view='0 0 64 64'):
    return ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s">%s'
            '<g stroke="%s" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round">%s</g></svg>\n') % (
        view, defs_for(body), INK, body)


G = {}

# ---- equipment ---------------------------------------------------------------------------------------------------
G['weapon'] = '''<g transform="rotate(45 32 32)">
<path d="M32 3 L38 10 V41 H26 V10 Z" fill="url(#s)"/><path d="M32 9 V38" stroke="#6f7b88" stroke-width="1.6" fill="none"/>
<rect x="17" y="41" width="30" height="6" rx="3" fill="url(#g)"/><rect x="28.5" y="47" width="7" height="9" rx="2" fill="url(#l)"/>
<circle cx="32" cy="58.5" r="3.6" fill="url(#g)"/></g>'''

G['golova'] = '''<path d="M32 12 C27 3 41 0 53 6 C46 6 40 8 36 12 Z" fill="url(#r)"/>
<path d="M10 53 V37 C10 21 20 11 32 11 C44 11 54 21 54 37 V53 H43 V45 H21 V53 Z" fill="url(#s)"/>
<path d="M12 24 C16 16 23 12 32 12" fill="none" stroke="#f3b93f" stroke-width="3"/>
<rect x="21" y="30" width="22" height="7" rx="1.5" fill="#2b1405"/><rect x="29.5" y="30" width="5" height="16" fill="url(#s)" stroke-width="1.6"/>
<rect x="10" y="48" width="44" height="6" rx="2" fill="url(#g)"/>'''

G['body'] = '''<path d="M22 7 L32 13 L42 7 L58 18 L52 33 L45 29 V57 H19 V29 L12 33 L6 18 Z" fill="url(#s)"/>
<path d="M22 7 L32 21 L42 7" fill="none"/><path d="M32 21 V57" fill="none" stroke="#6f7b88" stroke-width="1.6"/>
<path d="M6 18 L22 7 M58 18 L42 7" fill="none" stroke="#f3b93f" stroke-width="3.4"/>
<rect x="19" y="43" width="26" height="7" fill="url(#g)"/><circle cx="32" cy="46.5" r="3.2" fill="url(#r)" stroke-width="1.5"/>'''

G['pants'] = '''<path d="M17 8 H47 L52 58 H36 L32 30 L28 58 H12 Z" fill="url(#l)"/>
<path d="M32 30 V16" fill="none" stroke="#4a2410" stroke-width="1.6"/><path d="M20 40 H27 M37 40 H44" fill="none" stroke="#4a2410" stroke-width="1.6"/>
<rect x="17" y="8" width="30" height="9" rx="2" fill="url(#g)"/><rect x="29" y="10" width="6" height="5" rx="1" fill="#2b1405" stroke-width="1"/>'''

G['nogi'] = '''<path d="M20 8 H41 V34 C41 38 48 39 53 42 C60 46 59 56 52 56 H17 C11 56 11 49 14 45 L20 34 Z" fill="url(#l)"/>
<rect x="12" y="50" width="48" height="7" rx="3.5" fill="#4a2410"/>
<rect x="19" y="8" width="23" height="9" rx="2" fill="url(#g)"/><rect x="21" y="26" width="19" height="4.5" rx="1.5" fill="url(#g)" stroke-width="1.4"/>'''

G['ruki'] = '''<rect x="17" y="12" width="8" height="24" rx="4" fill="url(#s)"/><rect x="25" y="7" width="8" height="29" rx="4" fill="url(#s)"/>
<rect x="33" y="9" width="8" height="27" rx="4" fill="url(#s)"/><rect x="41" y="15" width="7.5" height="22" rx="3.75" fill="url(#s)"/>
<rect x="6" y="26" width="9" height="19" rx="4.5" fill="url(#s)" transform="rotate(-24 10 36)"/>
<rect x="16" y="29" width="33" height="20" rx="7" fill="url(#s)"/>
<rect x="14" y="46" width="37" height="11" rx="3" fill="url(#g)"/><circle cx="20" cy="51.5" r="1.6" fill="#2b1405" stroke="none"/><circle cx="32.5" cy="51.5" r="1.6" fill="#2b1405" stroke="none"/><circle cx="45" cy="51.5" r="1.6" fill="#2b1405" stroke="none"/>'''

G['shit'] = '''<path d="M32 4 L55 10 V30 C55 45 44 54 32 60 C20 54 9 45 9 30 V10 Z" fill="url(#g)"/>
<path d="M32 11 L48.5 15.5 V30 C48.5 40 41 47.5 32 52.5 C23 47.5 15.5 40 15.5 30 V15.5 Z" fill="url(#r)" stroke-width="1.6"/>
<rect x="29.5" y="13" width="5" height="38" fill="url(#g)" stroke-width="1.4"/><rect x="17" y="25" width="30" height="5" fill="url(#g)" stroke-width="1.4"/>
<circle cx="32" cy="27.5" r="5" fill="url(#b)" stroke-width="1.6"/>'''

G['plash'] = '''<path d="M21 8 H43 C47 22 54 40 59 56 C50 52 43 58 32 54 C21 58 14 52 5 56 C10 40 17 22 21 8 Z" fill="url(#r)"/>
<path d="M27 20 C25 34 24 44 21 54 M37 20 C39 34 40 44 43 54" fill="none" stroke="#8c140c" stroke-width="1.6"/>
<path d="M19 9 C24 15 40 15 45 9" fill="none" stroke="#f3b93f" stroke-width="4.4"/>
<circle cx="32" cy="15" r="5" fill="url(#g)"/><circle cx="32" cy="15" r="2.2" fill="url(#b)" stroke="none"/>'''

G['earring'] = '''<circle cx="32" cy="26" r="15" fill="none" stroke="#2b1405" stroke-width="8.6"/><circle cx="32" cy="26" r="15" fill="none" stroke="#f3b93f" stroke-width="4.6"/>
<circle cx="32" cy="9" r="4.2" fill="url(#g)"/>
<path d="M32 42 C24 50 24 57 32 61 C40 57 40 50 32 42 Z" fill="url(#b)"/><path d="M29 50 C28.5 53 29.5 55.5 31 57" fill="none" stroke="#fff" stroke-width="1.8" opacity=".8"/>'''

G['amulet'] = '''<path d="M13 5 C13 28 22 37 32 39 C42 37 51 28 51 5" fill="none" stroke="#2b1405" stroke-width="5.2"/>
<path d="M13 5 C13 28 22 37 32 39 C42 37 51 28 51 5" fill="none" stroke="#f3b93f" stroke-width="2.4" stroke-dasharray="4 2.2"/>
<circle cx="32" cy="48" r="12.5" fill="url(#g)"/><circle cx="32" cy="48" r="8" fill="url(#r)" stroke-width="1.6"/>
<ellipse cx="29.4" cy="44.6" rx="2.8" ry="1.7" fill="#fff" opacity=".8" stroke="none"/>'''

G['ring'] = '''<ellipse cx="32" cy="42" rx="19" ry="15" fill="none" stroke="#2b1405" stroke-width="10.4"/><ellipse cx="32" cy="42" rx="19" ry="15" fill="none" stroke="#f3b93f" stroke-width="6"/>
<path d="M20 17 L26 8 H38 L44 17 L32 32 Z" fill="url(#b)"/>
<path d="M20 17 H44 M26 8 L29 17 L32 32 M38 8 L35 17 L32 32" fill="none" stroke="#fff" stroke-width="1.2" opacity=".65"/>'''

# ---- consumables -------------------------------------------------------------------------------------------------
G['crystal'] = '''<circle cx="32" cy="33" r="30" fill="url(#w)" stroke="none"/>
<path d="M32 4 L50 17 V41 L32 60 L14 41 V17 Z" fill="url(#b)"/><path d="M32 4 L50 17 L32 30 L14 17 Z" fill="#e4f8ff" opacity=".8" stroke="none"/>
<path d="M32 30 V60 M14 17 L32 30 L50 17 M14 41 L32 30 L50 41" fill="none" stroke="#1a3d8a" stroke-width="1.4"/>
<path d="M53 5 L55 11 L61 13 L55 15 L53 21 L51 15 L45 13 L51 11 Z" fill="#fff6c0" stroke="none"/>'''

G['ore'] = '''<path d="M43 26 L45 11 L50 26 Z" fill="url(#v)" stroke-width="1.6"/><path d="M36 28 L36 15 L41 28 Z" fill="url(#v)" stroke-width="1.6"/>
<path d="M5 53 L10 34 L22 26 L34 32 L40 24 L54 32 L59 53 Z" fill="#9a9184"/>
<path d="M10 34 L22 41 L34 32 M22 26 L22 41 L18 53 M40 24 L38 41 L54 32 M38 41 L34 53" fill="none" stroke="#4a443c" stroke-width="1.4"/>
<circle cx="28" cy="47" r="3.6" fill="url(#g)" stroke-width="1.6"/><circle cx="47" cy="45" r="3" fill="url(#g)" stroke-width="1.6"/><circle cx="15" cy="46" r="2.6" fill="url(#g)" stroke-width="1.6"/>'''

G['scroll'] = '''<path d="M16 12 H48 V50 H16 Z" fill="url(#p)"/><rect x="10" y="6" width="44" height="10" rx="5" fill="url(#p)"/><rect x="10" y="46" width="44" height="10" rx="5" fill="url(#p)"/>
<path d="M22 23 H42 M22 30 H42 M22 37 H35" fill="none" stroke="#8a5a22" stroke-width="2"/>
<circle cx="44" cy="45" r="6.5" fill="url(#r)" stroke-width="1.8"/>'''

G['paw'] = '''<ellipse cx="13" cy="29" rx="5.4" ry="7.4" fill="url(#g)" transform="rotate(-22 13 29)"/><ellipse cx="25" cy="16" rx="5.6" ry="7.8" fill="url(#g)" transform="rotate(-8 25 16)"/>
<ellipse cx="39" cy="16" rx="5.6" ry="7.8" fill="url(#g)" transform="rotate(8 39 16)"/><ellipse cx="51" cy="29" rx="5.4" ry="7.4" fill="url(#g)" transform="rotate(22 51 29)"/>
<path d="M32 30 C41 30 49 40 47 49 C45 56 38 54 32 54 C26 54 19 56 17 49 C15 40 23 30 32 30 Z" fill="url(#g)"/>'''

G['potion'] = '''<path d="M26 8 H38 V20 C50 26 55 38 51 48 C47 59 17 59 13 48 C9 38 14 26 26 20 Z" fill="#dff3ff" fill-opacity=".35"/>
<path d="M14 38 C20 34 27 41 32 38 C38 35 44 40 50 38 C51.5 42 51.5 46 49.5 50 C45 59 19 59 14.5 50 C12.5 46 12.5 42 14 38 Z" fill="url(#b)" stroke-width="1.8"/>
<rect x="25" y="2.5" width="14" height="8" rx="2" fill="url(#l)"/><path d="M19 32 C17.5 38 18.5 43 21 47" fill="none" stroke="#fff" stroke-width="2.4" opacity=".85"/>
<circle cx="36" cy="46" r="2.2" fill="#fff" opacity=".7" stroke="none"/><circle cx="42" cy="43" r="1.5" fill="#fff" opacity=".7" stroke="none"/>
<path d="M53 8 L54.6 12.4 L59 14 L54.6 15.6 L53 20 L51.4 15.6 L47 14 L51.4 12.4 Z" fill="#fff2a8" stroke="none"/>'''

# ---- shop zone ---------------------------------------------------------------------------------------------------
G['bag'] = '''<path d="M23 9 H41 L37 20 C50 26 55 38 51 49 C47 59 17 59 13 49 C9 38 14 26 27 20 Z" fill="url(#l)"/>
<path d="M23 20 C28 24 36 24 41 20" fill="none" stroke="#f3b93f" stroke-width="4.4"/>
<circle cx="32" cy="41" r="10" fill="url(#g)"/><path d="M32 34 L34 39 L39.5 39.5 L35.4 43 L36.6 48.5 L32 45.6 L27.4 48.5 L28.6 43 L24.5 39.5 L30 39 Z" fill="#fff6c0" stroke-width="1"/>'''

G['diamond'] = '''<path d="M15 8 H49 L60 24 L32 59 L4 24 Z" fill="url(#b)"/><path d="M15 8 H49 L60 24 H4 Z" fill="#dff6ff" opacity=".55" stroke="none"/>
<path d="M4 24 H60 M15 8 L23 24 L32 59 M49 8 L41 24 L32 59 M23 24 L32 8 L41 24" fill="none" stroke="#1a3d8a" stroke-width="1.4"/>
<path d="M55 4 L56.6 8.4 L61 10 L56.6 11.6 L55 16 L53.4 11.6 L49 10 L53.4 8.4 Z" fill="#fff6c0" stroke="none"/>'''

G['crown'] = '''<path d="M7 47 L7 19 L20 31 L32 12 L44 31 L57 19 L57 47 Z" fill="url(#g)"/>
<rect x="7" y="45" width="50" height="10" rx="2.5" fill="url(#g)"/>
<circle cx="7" cy="17" r="3.6" fill="url(#r)" stroke-width="1.6"/><circle cx="32" cy="10" r="3.8" fill="url(#r)" stroke-width="1.6"/><circle cx="57" cy="17" r="3.6" fill="url(#r)" stroke-width="1.6"/>
<circle cx="32" cy="50" r="3.4" fill="url(#r)" stroke-width="1.5"/><circle cx="19" cy="50" r="2.6" fill="url(#b)" stroke-width="1.4"/><circle cx="45" cy="50" r="2.6" fill="url(#b)" stroke-width="1.4"/>'''

G['bars'] = '''<path d="M4 56 L9 41 H27 L32 56 Z" fill="url(#g)"/><path d="M32 56 L37 41 H55 L60 56 Z" fill="url(#g)"/><path d="M18 40 L23 25 H41 L46 40 Z" fill="url(#g)"/>
<path d="M11 45 H25 M39 45 H53 M25 29 H39" fill="none" stroke="#fff6c0" stroke-width="2" opacity=".8"/>
<path d="M50 6 L52 12 L58 14 L52 16 L50 22 L48 16 L42 14 L48 12 Z" fill="#fff6c0" stroke="none"/>'''

G['scales'] = '''<rect x="29.5" y="9" width="5" height="43" fill="url(#g)" stroke-width="1.8"/><rect x="18" y="50" width="28" height="7" rx="2.5" fill="url(#g)"/>
<rect x="6" y="13" width="52" height="5" rx="2.5" fill="url(#g)"/><circle cx="32" cy="9" r="4.2" fill="url(#g)"/>
<path d="M13 18 L5 37 M13 18 L21 37 M51 18 L43 37 M51 18 L59 37" fill="none" stroke-width="1.6"/>
<path d="M3 37 H23 C23 44 17 48 13 48 C9 48 3 44 3 37 Z" fill="url(#g)"/><path d="M41 37 H61 C61 44 55 48 51 48 C47 48 41 44 41 37 Z" fill="url(#g)"/>'''

G['coin'] = '''<circle cx="32" cy="32" r="26" fill="url(#g)"/><circle cx="32" cy="32" r="19.5" fill="none" stroke="#a8601a" stroke-width="2.4"/>
<path d="M32 15 L36.4 26.4 L48.6 27.2 L39.2 35 L42.2 46.8 L32 40.2 L21.8 46.8 L24.8 35 L15.4 27.2 L27.6 26.4 Z" fill="#fff0a8" stroke="#a8601a" stroke-width="1.6"/>'''

# ---- footer tabs / misc ------------------------------------------------------------------------------------------
G['menu'] = '''<rect x="9" y="12" width="46" height="9" rx="4.5" fill="url(#g)"/><rect x="9" y="27.5" width="46" height="9" rx="4.5" fill="url(#g)"/><rect x="9" y="43" width="46" height="9" rx="4.5" fill="url(#g)"/>'''

G['city'] = '''<rect x="17" y="27" width="30" height="29" fill="url(#n)"/>
<rect x="7" y="19" width="14" height="37" fill="url(#n)"/><rect x="43" y="19" width="14" height="37" fill="url(#n)"/>
<path d="M7 19 V14 H11 V17 H14 V14 H17 V17 H21 V19 M43 19 V14 H47 V17 H50 V14 H53 V17 H57 V19" fill="url(#n)" stroke-width="1.8"/>
<path d="M17 27 V23 H21 V26 H25 V23 H29 V26 H35 V23 H39 V26 H43 V23 H47 V27" fill="url(#n)" stroke-width="1.8"/>
<path d="M25 56 V44 C25 36 39 36 39 44 V56 Z" fill="#2b1405"/>
<path d="M14 14 V4 L24 7.5 L14 11" fill="url(#r)" stroke-width="1.6"/><rect x="12.8" y="12" width="2.4" height="3" fill="#2b1405" stroke="none"/>
<rect x="11.5" y="30" width="5" height="7" rx="2.5" fill="#2b1405" stroke="none"/><rect x="47.5" y="30" width="5" height="7" rx="2.5" fill="#2b1405" stroke="none"/>'''

G['world'] = '''<circle cx="46" cy="15" r="7" fill="url(#g)" stroke-width="1.8"/>
<path d="M2 54 L22 20 L34 40 L42 28 L62 54 Z" fill="url(#n)"/><path d="M22 20 L27.5 29 L22 27 L17 30 Z" fill="#fff" stroke-width="1.5"/>
<path d="M42 28 L46.4 35.5 L42 34 L38.4 36 Z" fill="#fff" stroke-width="1.5"/>
<path d="M2 54 H62" fill="none"/><path d="M12 56 L18 40 L24 56 Z" fill="url(#v)"/><path d="M14 46 L18 34 L22 46 Z" fill="url(#v)"/><rect x="17" y="55" width="2" height="4" fill="#4a2410" stroke="none"/>'''

G['hero'] = '''<circle cx="32" cy="21" r="12" fill="url(#g)"/><path d="M10 58 C10 41 21 34 32 34 C43 34 54 41 54 58 Z" fill="url(#g)"/>
<path d="M22 21 H42" fill="none" stroke-width="1.4" opacity=".0"/>'''

G['backpack'] = '''<path d="M25 11 C25 2 39 2 39 11" fill="none" stroke-width="3"/>
<rect x="13" y="12" width="38" height="46" rx="13" fill="url(#l)"/>
<path d="M13 32 C13 18 21 12 32 12 C43 12 51 18 51 32 Z" fill="#8d4c22"/>
<rect x="21" y="40" width="22" height="14" rx="4" fill="#8d4c22"/><rect x="28" y="28" width="8" height="8" rx="1.5" fill="url(#g)" stroke-width="1.6"/>
<path d="M13 44 V56 M51 44 V56" fill="none" stroke="#4a2410" stroke-width="1.4"/>'''

G['chat'] = '''<path d="M10 9 H54 C58 9 60 11 60 15 V37 C60 41 58 43 54 43 H31 L19 55 V43 H10 C6 43 4 41 4 37 V15 C4 11 6 9 10 9 Z" fill="url(#p)"/>
<circle cx="19" cy="26" r="3.6" fill="#8a3a14"/><circle cx="32" cy="26" r="3.6" fill="#8a3a14"/><circle cx="45" cy="26" r="3.6" fill="#8a3a14"/>'''

G['clan'] = '''<rect x="9" y="4" width="5" height="56" rx="2" fill="url(#g)"/><circle cx="11.5" cy="5" r="3.4" fill="url(#g)"/>
<path d="M14 9 H56 V46 L35 38 L14 46 Z" fill="url(#r)"/>
<path d="M35 15 L38.2 22.4 L46 23 L40 28 L41.9 35.6 L35 31.5 L28.1 35.6 L30 28 L24 23 L31.8 22.4 Z" fill="url(#g)" stroke-width="1.5"/>'''

G['fish'] = '''<path d="M5 33 C14 14 37 12 47 28 L60 16 V50 L47 38 C37 54 14 52 5 33 Z" fill="url(#b)"/>
<path d="M47 28 L60 16 V50 L47 38 Z" fill="url(#g)"/>
<path d="M14 36 C22 40 32 40 40 34" fill="none" stroke="#1a3d8a" stroke-width="1.4" opacity=".75"/>
<path d="M29 21 C32 28 32 38 29 45 M37 22 C39.5 28 39.5 38 37 44" fill="none" stroke="#1a3d8a" stroke-width="1.4" opacity=".6"/>
<circle cx="17" cy="30" r="3.4" fill="#fff" stroke-width="1.6"/><circle cx="17.7" cy="30.3" r="1.5" fill="#2b1405" stroke="none"/>'''

G['quest'] = G['scroll']
G['casino'] = '''<rect x="8" y="8" width="38" height="38" rx="8" fill="url(#p)"/>
<circle cx="19" cy="19" r="3.6" fill="#8a1a10" stroke-width="1.4"/><circle cx="27" cy="27" r="3.6" fill="#8a1a10" stroke-width="1.4"/><circle cx="35" cy="35" r="3.6" fill="#8a1a10" stroke-width="1.4"/>
<circle cx="35" cy="19" r="3.6" fill="#8a1a10" stroke-width="1.4"/><circle cx="19" cy="35" r="3.6" fill="#8a1a10" stroke-width="1.4"/>
<path d="M46 24 L58 30 L52 54 L26 46" fill="none" stroke-width="2"/>'''

def main():
    os.makedirs(OUT, exist_ok=True)
    n = 0
    for name, body in sorted(G.items()):
        with open(os.path.join(OUT, 'g-%s.svg' % name), 'w', encoding='utf-8', newline='\n') as f:
            f.write(svg(body))
        n += 1
    print('wrote %d glyphs to %s' % (n, os.path.abspath(OUT)))


if __name__ == '__main__':
    main()
