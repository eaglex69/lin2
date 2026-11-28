#!/usr/bin/env python3
"""
Draws theme/fantasy/btn-send.png: the copper «Отправить» button.  The old pages print this button as an empty
<input class="button_medium3" value=""> and rely on a picture that carries the word, so the theme needs a picture, too.
The inside of the button only (copper texture, gloss, small caps lettering); the frame and the shadow come from the CSS.

Needs Pillow and a TrueType file of Cormorant SC Bold with Cyrillic glyphs (theme/fonts/*.woff2 is the same font:
convert it with fontTools:  python3 -c "from fontTools.ttLib import TTFont; f=TTFont('theme/fonts/cormorant-700-cyr.woff2'); f.flavor=None; f.save('/tmp/cormorant-cyr.ttf')").
Usage:  python3 tools/php8/art/make_btn.py /tmp/cormorant-cyr.ttf [outdir]
"""
import os
import sys

from PIL import Image, ImageDraw, ImageFont

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', '..')
FONT = sys.argv[1] if len(sys.argv) > 1 else '/tmp/cormorant-cyr.ttf'
OUT = sys.argv[2] if len(sys.argv) > 2 else os.path.join(ROOT, 'theme', 'fantasy')
W, H = 192, 64          # drawn at 2x, shown at 96 x 32


def make(text, name):
    tex = Image.open(os.path.join(ROOT, 'theme', 'fantasy', 'tx-copper.jpg')).convert('RGB')
    img = Image.new('RGB', (W, H))
    img.paste(tex.crop((20, 30, 20 + W, 30 + H)), (0, 0))
    over = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    px = over.load()
    for y in range(H):
        for x in range(W):
            if y < H * 0.47:
                a = int(255 * (0.34 - 0.22 * (y / (H * 0.47))))
                px[x, y] = (255, 236, 200, a)
            else:
                t = (y - H * 0.47) / (H * 0.53)
                px[x, y] = (30, 10, 0, int(255 * 0.18 * t))
    img = Image.alpha_composite(img.convert('RGBA'), over)
    font = ImageFont.truetype(FONT, 30)
    d = ImageDraw.Draw(img)
    # small caps are in the font itself: capital first letter + small capitals
    box = d.textbbox((0, 0), text, font=font)
    tw, th = box[2] - box[0], box[3] - box[1]
    x = (W - tw) // 2 - box[0]
    y = (H - th) // 2 - box[1] - 1
    d.text((x, y + 2), text, font=font, fill=(58, 26, 8, 255))          # shadow
    d.text((x, y), text, font=font, fill=(253, 238, 214, 255))
    os.makedirs(OUT, exist_ok=True)
    p = os.path.join(OUT, name)
    img.convert('RGB').save(p, 'PNG', optimize=True)
    print('wrote %s  %dx%d  %d bytes  (text %dx%d)' % (os.path.abspath(p), W, H, os.path.getsize(p), tw, th))


if __name__ == '__main__':
    make('Отправить', 'btn-send.png')
