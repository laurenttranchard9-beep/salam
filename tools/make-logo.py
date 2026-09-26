"""
Fabrique le logo « miam » (le mot en dégradé du pied de page) avec la mention
« Créateur de site internet, menu et motion design ».

Les lettres sont vectorisées depuis les polices du site (Bricolage Grotesque et
Unbounded, licence OFL) : les SVG produits s'ouvrent partout (navigateur,
Illustrator, Inkscape, Canva…) sans avoir besoin des polices.

Usage : python3 tools/make-logo.py   (fontTools et uharfbuzz requis)
Les fichiers sont écrits dans logo/.
"""
import io
import math
import os

import uharfbuzz as hb
from fontTools.pens.boundsPen import BoundsPen
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FONTS = os.path.join(ROOT, 'www', 'assets', 'fonts')
OUT = os.path.join(ROOT, 'logo')

WORD = 'miam'
TAGLINE = 'Créateur de site internet, menu et motion design'
INK = '#24130d'
CREAM = '#fff6ea'
# Le dégradé du pied de page : linear-gradient(100deg, #ff7240 0%, #ffd84d 38%, #f0532d 62%, #8a5cf6 100%)
STOPS = [(0, '#ff7240'), (0.38, '#ffd84d'), (0.62, '#f0532d'), (1, '#8a5cf6')]
# Sur fond clair, le jaune est un peu plus soutenu pour rester lisible
STOPS_LIGHT = [(0, '#ff6a36'), (0.38, '#ffbf2e'), (0.62, '#ec4b27'), (1, '#8452f2')]


class Font:
    def __init__(self, file, axes):
        font = TTFont(os.path.join(FONTS, file))
        font = instantiateVariableFont(font, axes)
        font.flavor = None  # HarfBuzz lit le TrueType brut, pas le WOFF2
        buf = io.BytesIO()
        font.save(buf)
        self.tt = font
        self.glyphs = font.getGlyphSet()
        self.upm = font['head'].unitsPerEm
        self.hb = hb.Font(hb.Face(buf.getvalue()))

    def shape(self, text):
        buf = hb.Buffer()
        buf.add_str(text)
        buf.guess_segment_properties()
        hb.shape(self.hb, buf, {'kern': True, 'liga': True})
        order = self.tt.getGlyphOrder()
        return [(order[info.codepoint], pos.x_advance, pos.x_offset, pos.y_offset)
                for info, pos in zip(buf.glyph_infos, buf.glyph_positions)]

    def run(self, text, size, tracking=0.0):
        """Glyphes posés sur une ligne : [(nom, x, y)] en unités de la police, et la largeur."""
        x = 0.0
        placed = []
        shaped = self.shape(text)
        for i, (name, adv, dx, dy) in enumerate(shaped):
            placed.append((name, x + dx, dy))
            x += adv + (tracking * self.upm if i < len(shaped) - 1 else 0)
        return placed, x

    def path(self, name, scale, ox, oy):
        pen = SVGPathPen(self.glyphs, ntos=lambda v: ('%.2f' % v).rstrip('0').rstrip('.'))
        self.glyphs[name].draw(TransformPen(pen, (scale, 0, 0, -scale, ox, oy)))
        return pen.getCommands()

    def bounds(self, name):
        pen = BoundsPen(self.glyphs)
        self.glyphs[name].draw(pen)
        return pen.bounds


display = Font('bricolage-grotesque.woff2', {'opsz': 96, 'wght': 800})
ui = Font('unbounded.woff2', {'wght': 500})


def word_block(size):
    """Le mot « miam » : chemins par lettre, boîte d'encre (x0, y0, x1, y1) avec la ligne de base à y = 0."""
    placed, _ = display.run(WORD, size, tracking=-0.06)  # le même resserrement que dans le pied de page
    s = size / display.upm
    letters, x0, y0, x1, y1 = [], math.inf, math.inf, -math.inf, -math.inf
    for name, gx, gy in placed:
        b = display.bounds(name)
        if b:
            x0, x1 = min(x0, (gx + b[0]) * s), max(x1, (gx + b[2]) * s)
            y0, y1 = min(y0, -(gy + b[3]) * s), max(y1, -(gy + b[1]) * s)
        letters.append((name, gx * s, gy * s))
    return letters, (x0, y0, x1, y1)


def tagline_block(width, text, tracking=0.12):
    """La mention, en capitales, calée exactement sur la largeur voulue."""
    placed, raw = ui.run(text.upper(), 1000, tracking=tracking)
    first = ui.bounds(placed[0][0])
    last_name, last_x, _ = placed[-1]
    last = ui.bounds(last_name)
    ink = (last_x + last[2]) - first[0]  # largeur d'encre, sans les approches du début et de la fin
    size = width / (ink / ui.upm)
    s = size / ui.upm
    cap = ui.tt['OS/2'].sCapHeight * s
    return [(n, (gx - first[0]) * s, gy * s) for n, gx, gy in placed], size, cap


def gradient_def(gid, box, stops, angle=100, spread=0.1667, animate=False):
    """Dégradé CSS (angle en degrés) converti en dégradé SVG, étalé un peu au-delà du mot comme dans le pied de page."""
    x0, y0, x1, y1 = box
    w = x1 - x0
    x0, x1 = x0 - w * spread, x1 + w * spread
    cx, cy, w, h = (x0 + x1) / 2, (y0 + y1) / 2, x1 - x0, y1 - y0
    a = math.radians(angle)
    dx, dy = math.sin(a), -math.cos(a)
    half = (abs(w * math.sin(a)) + abs(h * math.cos(a))) / 2
    sx, sy, ex, ey = cx - dx * half, cy - dy * half, cx + dx * half, cy + dy * half
    stop_tags = ''.join(f'<stop offset="{o:g}" stop-color="{c}"/>' for o, c in stops)
    anim = ''
    if animate:
        # Le dégradé glisse lentement d'un côté à l'autre, en boucle
        shift = (ex - sx) * 0.07
        anim = (f'<animateTransform attributeName="gradientTransform" type="translate" values="0 0; {-shift:.1f} 0; {shift:.1f} 0; 0 0" '
                f'keyTimes="0; .35; .75; 1" dur="9s" begin="1.6s" repeatCount="indefinite" calcMode="spline" '
                f'keySplines=".45 0 .55 1; .45 0 .55 1; .45 0 .55 1"/>')
    return (f'<linearGradient id="{gid}" gradientUnits="userSpaceOnUse" x1="{sx:.1f}" y1="{sy:.1f}" x2="{ex:.1f}" y2="{ey:.1f}" spreadMethod="reflect">'
            f'{stop_tags}{anim}</linearGradient>')


def build(variant):
    """
    variant : dict(bg, tag, stops, layout='line'|'stack', canvas=(w, h) ou None, animated)
    """
    size = 400
    letters, (wx0, wy0, wx1, wy1) = word_block(size)
    word_w = wx1 - wx0
    if variant.get('layout') == 'stack':
        lines = ['Créateur de site internet,', 'menu et motion design']
        # Deux lignes centrées, de même taille : la plus longue fait la largeur du mot
        widest = max(lines, key=lambda t: ui.run(t.upper(), 1000, 0.12)[1])
        _, tsize, cap = tagline_block(word_w * 0.98, widest)
        tag_lines = []
        for t in lines:
            p, _ = ui.run(t.upper(), tsize, 0.12)
            k = tsize / ui.upm
            first = ui.bounds(p[0][0])
            last = ui.bounds(p[-1][0])
            ink = ((p[-1][1] + last[2]) - first[0]) * k
            tag_lines.append(([(n, (gx - first[0]) * k, gy * k) for n, gx, gy in p], ink))
    else:
        tag, tsize, cap = tagline_block(word_w, TAGLINE)
        tag_lines = [(tag, word_w)]

    gap = size * 0.1
    line_gap = cap * 1.25
    pad = size * 0.16
    tag_top = wy1 + gap
    block_h = (wy1 - wy0) + gap + cap * len(tag_lines) + line_gap * (len(tag_lines) - 1)
    content_w, content_h = word_w, block_h

    if variant.get('canvas'):
        cw, ch = variant['canvas']
        scale = min((cw * 0.78) / content_w, (ch * 0.62) / content_h)
    else:
        scale = 1
        cw, ch = content_w + 2 * pad, content_h + 2 * pad
    # Origine : coin haut-gauche du bloc
    ox = (cw - content_w * scale) / 2
    oy = (ch - content_h * scale) / 2

    def X(x):
        return ox + (x - wx0) * scale

    def Y(y):
        return oy + (y - wy0) * scale

    stops = variant['stops']
    box = (X(wx0), Y(wy0), X(wx1), Y(wy1))
    animated = variant.get('animated', False)
    defs = [gradient_def('miam-degrade', box, stops, animate=animated)]
    parts = []

    s = scale
    word_paths = [display.path(name, s * size / display.upm, X(gx), Y(0) - gy * s) for name, gx, gy in letters]
    if animated:
        # Chaque lettre monte depuis la ligne de base, comme les titres du site
        clip_top = Y(wy0) - size * 0.2 * s
        defs.append(f'<clipPath id="miam-masque"><rect x="0" y="{clip_top:.1f}" width="{cw:.1f}" height="{(Y(wy1) - clip_top + size * 0.02 * s):.1f}"/></clipPath>')
        letters_svg = ''.join(f'<path class="l" style="animation-delay:{0.15 + i * 0.09:.2f}s" d="{d}"/>' for i, d in enumerate(word_paths))
        parts.append(f'<g clip-path="url(#miam-masque)" fill="url(#miam-degrade)">{letters_svg}</g>')
    else:
        parts.append(f'<g fill="url(#miam-degrade)"><path d="{" ".join(word_paths)}"/></g>')

    # La mention
    tag_parts = []
    y_cursor = tag_top
    n = 0
    for placed, ink in tag_lines:
        line_x = (word_w - ink) / 2
        baseline = y_cursor + cap
        for name, gx, gy in placed:
            d = ui.path(name, s * tsize / ui.upm, ox + (line_x + gx) * s, Y(baseline) - gy * s)
            if not d:
                continue
            if animated:
                tag_parts.append(f'<path class="t" style="animation-delay:{0.75 + n * 0.018:.3f}s" d="{d}"/>')
            else:
                tag_parts.append(d)
            n += 1
        y_cursor = baseline + line_gap
    if animated:
        parts.append(f'<g fill="{variant["tag"]}">{"".join(tag_parts)}</g>')
    else:
        parts.append(f'<path fill="{variant["tag"]}" d="{" ".join(tag_parts)}"/>')

    bg = f'<rect width="100%" height="100%" fill="{variant["bg"]}"/>' if variant.get('bg') else ''
    style = ''
    if animated:
        style = ('<style>'
                 '.l{transform-box:fill-box;transform-origin:0 100%;animation:rise 1.05s cubic-bezier(.22,1,.36,1) both}'
                 '.t{transform-box:fill-box;animation:fade .7s cubic-bezier(.22,1,.36,1) both}'
                 '@keyframes rise{from{transform:translateY(115%) rotate(6deg)}}'
                 '@keyframes fade{from{opacity:0;transform:translateY(40%)}}'
                 '@media (prefers-reduced-motion:reduce){.l,.t{animation-duration:.01s}}'
                 '</style>')
    title = f'<title>miam · {TAGLINE}</title>'
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {cw:.0f} {ch:.0f}" width="{cw:.0f}" height="{ch:.0f}" role="img">'
            f'{title}{style}<defs>{"".join(defs)}</defs>{bg}{"".join(parts)}</svg>')


VARIANTS = {
    'miam-logo-fond-sombre': dict(bg=INK, tag=CREAM, stops=STOPS),
    'miam-logo-fond-clair': dict(bg=CREAM, tag=INK, stops=STOPS_LIGHT),
    'miam-logo-transparent-texte-clair': dict(bg=None, tag=CREAM, stops=STOPS),
    'miam-logo-transparent-texte-fonce': dict(bg=None, tag=INK, stops=STOPS_LIGHT),
    'miam-logo-carre': dict(bg=INK, tag=CREAM, stops=STOPS, layout='stack', canvas=(1080, 1080)),
    'miam-logo-anime': dict(bg=INK, tag=CREAM, stops=STOPS, canvas=(1200, 630), animated=True),
}

if __name__ == '__main__':
    os.makedirs(OUT, exist_ok=True)
    for name, variant in VARIANTS.items():
        with open(os.path.join(OUT, name + '.svg'), 'w', encoding='utf-8') as f:
            f.write(build(variant))
        print('écrit', name + '.svg')
