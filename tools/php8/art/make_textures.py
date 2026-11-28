#!/usr/bin/env python3
"""
Draws the grunge textures of the "fantasy" theme (tileable, no external resources):
    theme/fantasy/tx-bg.jpg      page background (dark brown, cracks, scratches)
    theme/fantasy/tx-copper.jpg  copper: panels, buttons, tabs
    theme/fantasy/tx-gold.jpg    gold: primary buttons, titles
They are made of tileable fractal noise (FFT) + hairline cracks + scratches; the same numbers always give the same
pictures.  Needs numpy and Pillow.   Usage:  python3 tools/php8/art/make_textures.py [outdir]
"""
import os
import sys

import numpy as np
from PIL import Image, ImageDraw

OUT = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', '..', 'theme', 'fantasy')


def fnoise(rng, n, beta, sx=1.0, sy=1.0):
    """tileable noise (n x n), power ~ 1/f^beta, zero mean, unit variance; sx / sy stretch the spectrum (streaks)"""
    wn = rng.standard_normal((n, n))
    fy = np.fft.fftfreq(n)[:, None] * sy
    fx = np.fft.fftfreq(n)[None, :] * sx
    f = np.sqrt(fx * fx + fy * fy)
    f[0, 0] = 1.0
    spec = np.fft.fft2(wn) / f ** (beta / 2.0)
    spec[0, 0] = 0
    o = np.real(np.fft.ifft2(spec))
    return (o - o.mean()) / (o.std() + 1e-9)


def smooth(x, a, b):
    t = np.clip((x - a) / (b - a), 0.0, 1.0)
    return t * t * (3 - 2 * t)


def ridge(noise, width):
    """thin curved lines where the noise crosses zero (cracks, veins)"""
    return np.exp(-(noise / width) ** 2)


def scratches(rng, n, count, lo=0.15, hi=0.6):
    """short hairlines (wrap around the edges, so the tile stays seamless), 0..1"""
    img = Image.new('L', (n, n), 0)
    d = ImageDraw.Draw(img)
    for _ in range(count):
        x, y = rng.uniform(0, n, 2)
        ang = rng.uniform(0, np.pi)
        ln = rng.uniform(n * lo * 0.3, n * hi * 0.5)
        dx, dy = np.cos(ang) * ln, np.sin(ang) * ln
        val = int(rng.uniform(60, 255))
        for ox in (-n, 0, n):
            for oy in (-n, 0, n):
                d.line([(x + ox, y + oy), (x + dx + ox, y + dy + oy)], fill=val, width=1)
    return np.asarray(img, dtype=np.float32) / 255.0


def speckles(rng, n, count):
    m = np.zeros((n, n), np.float32)
    ys = rng.integers(0, n, count)
    xs = rng.integers(0, n, count)
    m[ys, xs] = rng.uniform(0.3, 1.0, count)
    return m


def ramp(L, stops):
    xs = [s[0] for s in stops]
    out = np.zeros(L.shape + (3,), np.float32)
    for c in range(3):
        out[..., c] = np.interp(L, xs, [s[1][c] for s in stops])
    return out


def save(rgb, name, quality):
    img = Image.fromarray(np.clip(rgb, 0, 255).astype(np.uint8), 'RGB')
    os.makedirs(OUT, exist_ok=True)
    p = os.path.join(OUT, name)
    img.save(p, 'JPEG', quality=quality, optimize=True, progressive=True)
    print('wrote %s  %dx%d  %d bytes' % (os.path.abspath(p), img.width, img.height, os.path.getsize(p)))


def make_bg(n=384):
    rng = np.random.default_rng(11)
    a, b, c = fnoise(rng, n, 2.8), fnoise(rng, n, 2.0), fnoise(rng, n, 1.2)
    g = rng.standard_normal((n, n))
    L = 0.47 + 0.10 * a + 0.07 * b + 0.04 * c + 0.018 * g
    L -= 0.16 * ridge(fnoise(rng, n, 2.4), 0.09)                       # cracks
    L -= 0.10 * smooth(fnoise(rng, n, 2.6), 0.9, 1.8)                  # dark stains
    L += 0.07 * scratches(rng, n, 70) - 0.10 * scratches(rng, n, 50)   # scratches
    L += 0.10 * speckles(rng, n, 500) - 0.10 * speckles(rng, n, 500)
    rgb = ramp(np.clip(L, 0, 1), [(0.0, (12, 7, 5)), (0.30, (28, 18, 12)), (0.50, (46, 31, 22)),
                                  (0.70, (68, 48, 34)), (1.0, (104, 77, 56))])
    save(rgb, 'tx-bg.jpg', 80)


def make_copper(n=256):
    rng = np.random.default_rng(23)
    a = fnoise(rng, n, 2.6)
    streak = fnoise(rng, n, 1.8, sx=0.45, sy=2.4)
    c = fnoise(rng, n, 1.1)
    g = rng.standard_normal((n, n))
    L = 0.56 + 0.075 * a + 0.05 * streak + 0.03 * c + 0.016 * g
    L -= 0.11 * smooth(fnoise(rng, n, 2.3), 0.7, 1.9)                  # dark rust stains
    L += 0.09 * smooth(fnoise(rng, n, 2.5), 0.7, 1.7)                  # warm light patches
    L -= 0.05 * ridge(fnoise(rng, n, 2.2), 0.06)
    L += 0.05 * scratches(rng, n, 36) - 0.06 * scratches(rng, n, 26)
    L += 0.05 * speckles(rng, n, 220) - 0.05 * speckles(rng, n, 220)
    rgb = ramp(np.clip(L, 0, 1), [(0.0, (52, 22, 8)), (0.28, (104, 52, 22)), (0.52, (158, 90, 40)),
                                  (0.78, (200, 124, 62)), (1.0, (236, 168, 94))])
    save(rgb, 'tx-copper.jpg', 82)


def make_gold(n=160):
    rng = np.random.default_rng(37)
    a, c = fnoise(rng, n, 2.4), fnoise(rng, n, 1.2)
    g = rng.standard_normal((n, n))
    L = 0.62 + 0.07 * a + 0.045 * c + 0.025 * g
    L -= 0.10 * smooth(fnoise(rng, n, 2.3), 0.7, 1.7)
    L += 0.06 * scratches(rng, n, 30) - 0.07 * scratches(rng, n, 24)
    rgb = ramp(np.clip(L, 0, 1), [(0.0, (112, 60, 4)), (0.35, (186, 118, 12)), (0.62, (236, 172, 34)),
                                  (0.82, (252, 208, 78)), (1.0, (255, 238, 150))])
    save(rgb, 'tx-gold.jpg', 84)


if __name__ == '__main__':
    make_bg()
    make_copper()
    make_gold()
