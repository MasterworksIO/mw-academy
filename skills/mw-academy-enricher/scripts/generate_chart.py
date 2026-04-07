#!/usr/bin/env python3
"""
Masterworks Academy SVG Chart Generator v2

Produces polished, Figma-ready SVG charts matching the Masterworks visual style:
- Article charts: light background (#F7F7F8), clean typography, subtle grids
- Social cards: dark background (#131217), bold stats, brand accents

Style references: Banksy vs Basquiat comparison panels, Banksy Demand Over Time chart,
Masterworks Art Market Indices chart, comparison tables.

No external dependencies. Python stdlib only.

Usage:
    python3 generate_chart.py --type line --title "Title" --data '{...}' --output out.svg
    python3 generate_chart.py --type line --title "Title" --data '{...}' --output out.svg --theme dark
    python3 generate_chart.py --type bar --title "Title" --data '{...}' --output out.svg
    python3 generate_chart.py --type horizontal-bar --title "Title" --data '{...}' --output out.svg
    python3 generate_chart.py --type comparison-table --title "Title" --data '{...}' --output out.svg
    python3 generate_chart.py --type social-card --title "Title" --data '{...}' --output out.svg
"""

import argparse
import json
import math
import os

# ── Brand palette ──────────────────────────────────────────────────────────────

# Light theme (article embeds)
LIGHT_BG = "#F7F7F8"
LIGHT_CARD = "#FFFFFF"
LIGHT_BORDER = "#E5E5E7"
LIGHT_GRID = "#E5E5E7"
LIGHT_TEXT_PRIMARY = "#1A1A1A"
LIGHT_TEXT_SECONDARY = "#6B6B6B"
LIGHT_TEXT_MUTED = "#999999"

# Dark theme (social, hero)
DARK_BG = "#131217"
DARK_CARD = "#1A1A2E"
DARK_GRID = "#2A2A3E"
DARK_TEXT_PRIMARY = "#FFFFFF"
DARK_TEXT_SECONDARY = "#E8E8E8"
DARK_TEXT_MUTED = "#888899"

# Data colors
FREQ_PURPLE = "#495DE5"
PURPLE_LIGHT = "#6B6BF0"  # lighter variant for area fills
GRAY_PRIMARY = "#9CA3AF"  # secondary data series
GRAY_DARK = "#6B7280"     # darker gray for emphasis
MED_GREEN = "#24CB71"     # positive/accent (used sparingly)

# Shared
FONT_TITLE = "Tiempos Headline, Georgia, Times New Roman, serif"
FONT_BODY = "Neue Haas Grotesk, Helvetica Neue, Helvetica, Arial, sans-serif"

DEFAULT_WIDTH = 1200
DEFAULT_HEIGHT = 680


def escape_xml(s):
    return str(s).replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace('"', "&quot;")


def nice_ticks(min_val, max_val, count=5):
    if min_val == max_val:
        return [min_val]
    raw = (max_val - min_val) / count
    mag = 10 ** math.floor(math.log10(max(abs(raw), 1e-10)))
    r = raw / mag
    if r <= 1.5: step = mag
    elif r <= 3: step = 2 * mag
    elif r <= 7: step = 5 * mag
    else: step = 10 * mag
    start = math.floor(min_val / step) * step
    ticks = []
    v = start
    while v <= max_val + step * 0.5:
        ticks.append(round(v, 10))
        v += step
    return ticks


def fmt(v, suffix=""):
    """Format a number for axis/label display."""
    if abs(v) >= 1_000_000_000:
        return f"${v/1e9:.1f}B{suffix}"
    if abs(v) >= 1_000_000:
        return f"${v/1e6:.1f}M{suffix}"
    if abs(v) >= 1_000:
        return f"{v/1e3:.0f}k{suffix}" if v == int(v) else f"{v:,.0f}{suffix}"
    if v == int(v):
        return f"{int(v)}{suffix}"
    return f"{v:.1f}{suffix}"


class Theme:
    def __init__(self, dark=False):
        self.dark = dark
        if dark:
            self.bg = DARK_BG
            self.card = DARK_CARD
            self.grid = DARK_GRID
            self.text1 = DARK_TEXT_PRIMARY
            self.text2 = DARK_TEXT_SECONDARY
            self.muted = DARK_TEXT_MUTED
        else:
            self.bg = LIGHT_BG
            self.card = LIGHT_CARD
            self.grid = LIGHT_GRID
            self.text1 = LIGHT_TEXT_PRIMARY
            self.text2 = LIGHT_TEXT_SECONDARY
            self.muted = LIGHT_TEXT_MUTED


# ── Chart: Line ────────────────────────────────────────────────────────────────

def generate_line_chart(data, title, w, h, source, subtitle, theme):
    t = Theme(theme == "dark")
    pad = {"top": 90, "right": 140, "bottom": 70, "left": 65}
    series = data["series"]

    cl, cr = pad["left"], w - pad["right"]
    ct, cb = pad["top"], h - pad["bottom"]
    cw, ch = cr - cl, cb - ct

    all_x = [p["year"] for s in series for p in s["values"]]
    all_y = [p["value"] for s in series for p in s["values"]]
    x0, x1 = min(all_x), max(all_x)
    y0 = min(0, min(all_y))
    y1 = max(all_y) * 1.12
    yticks = nice_ticks(y0, y1, 5)
    if yticks:
        y0, y1 = min(yticks), max(yticks)

    xp = lambda yr: cl + (yr - x0) / max(x1 - x0, 1) * cw
    yp = lambda v: cb - (v - y0) / max(y1 - y0, 1) * ch

    colors = [FREQ_PURPLE, GRAY_PRIMARY, GRAY_DARK, MED_GREEN, PURPLE_LIGHT]
    widths = [2.5, 1.8, 1.8, 1.8, 1.8]  # primary thicker

    o = []
    # Background
    o.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}">')
    o.append(f'<rect width="{w}" height="{h}" fill="{t.bg}" rx="12"/>')

    # Title block
    o.append(f'<text x="{pad["left"]}" y="38" fill="{t.text1}" font-family="{FONT_TITLE}" font-size="22" font-weight="700">{escape_xml(title)}</text>')
    if subtitle:
        o.append(f'<text x="{pad["left"]}" y="60" fill="{t.muted}" font-family="{FONT_BODY}" font-size="13">{escape_xml(subtitle)}</text>')

    # Y-axis grid + labels
    for tick in yticks:
        y = yp(tick)
        o.append(f'<line x1="{cl}" y1="{y:.1f}" x2="{cr}" y2="{y:.1f}" stroke="{t.grid}" stroke-width="0.75"/>')
        label = fmt(tick)
        o.append(f'<text x="{cl - 12}" y="{y + 4:.1f}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="12" text-anchor="end">{label}</text>')

    # X-axis labels
    xrange = x1 - x0
    xstep = max(1, xrange // 6)
    for yr in range(x0, x1 + 1, xstep):
        x = xp(yr)
        o.append(f'<text x="{x:.1f}" y="{cb + 28}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="12" text-anchor="middle">{yr}</text>')

    # Area fill for primary series (subtle)
    if len(series) > 0:
        pts = sorted(series[0]["values"], key=lambda p: p["year"])
        fill_color = FREQ_PURPLE if not t.dark else FREQ_PURPLE
        area_path = f"M {xp(pts[0]['year']):.1f} {cb:.1f}"
        for p in pts:
            area_path += f" L {xp(p['year']):.1f} {yp(p['value']):.1f}"
        area_path += f" L {xp(pts[-1]['year']):.1f} {cb:.1f} Z"
        o.append(f'<path d="{area_path}" fill="{fill_color}" opacity="0.06"/>')

    # Data lines
    for i, s in enumerate(series):
        color = colors[i % len(colors)]
        sw = widths[i % len(widths)]
        pts = sorted(s["values"], key=lambda p: p["year"])

        # Line path
        d = " ".join(
            f"{'M' if j == 0 else 'L'} {xp(p['year']):.1f} {yp(p['value']):.1f}"
            for j, p in enumerate(pts)
        )
        dash = "" if i == 0 else ' stroke-dasharray="6,4"' if i > 1 else ""
        o.append(f'<path d="{d}" fill="none" stroke="{color}" stroke-width="{sw}"{dash} stroke-linecap="round" stroke-linejoin="round"/>')

        # End point + label
        last = pts[-1]
        lx, ly = xp(last["year"]), yp(last["value"])
        o.append(f'<circle cx="{lx:.1f}" cy="{ly:.1f}" r="4" fill="{color}"/>')
        # Label with value
        label_text = s["name"]
        if "label_suffix" in s:
            label_text += f" {s['label_suffix']}"
        o.append(f'<text x="{lx + 12:.1f}" y="{ly - 2:.1f}" fill="{color}" font-family="{FONT_BODY}" font-size="12" font-weight="600">{escape_xml(label_text)}</text>')

    # Source
    if source:
        o.append(f'<text x="{pad["left"]}" y="{h - 18}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="10" opacity="0.65">Source: {escape_xml(source)}</text>')

    o.append("</svg>")
    return "\n".join(o)


# ── Chart: Bar ─────────────────────────────────────────────────────────────────

def generate_bar_chart(data, title, w, h, source, subtitle, theme):
    t = Theme(theme == "dark")
    pad = {"top": 90, "right": 50, "bottom": 70, "left": 65}
    cats = data["categories"]
    series = data["series"]

    cl, cr = pad["left"], w - pad["right"]
    ct, cb = pad["top"], h - pad["bottom"]
    cw, ch = cr - cl, cb - ct

    all_v = [v for s in series for v in s["values"]]
    y0 = 0
    y1 = max(all_v) * 1.15
    yticks = nice_ticks(y0, y1, 5)
    if yticks: y1 = max(yticks)

    yp = lambda v: cb - (v - y0) / max(y1 - y0, 1) * ch

    n_cats = len(cats)
    n_ser = len(series)
    gw = cw / n_cats
    margin = gw * 0.2
    bar_area = gw - margin * 2
    bw = bar_area / n_ser
    gap = bw * 0.12
    bw -= gap

    bar_colors = [FREQ_PURPLE, GRAY_PRIMARY, MED_GREEN, GRAY_DARK]

    o = []
    o.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}">')
    o.append(f'<rect width="{w}" height="{h}" fill="{t.bg}" rx="12"/>')
    o.append(f'<text x="{pad["left"]}" y="38" fill="{t.text1}" font-family="{FONT_TITLE}" font-size="22" font-weight="700">{escape_xml(title)}</text>')
    if subtitle:
        o.append(f'<text x="{pad["left"]}" y="60" fill="{t.muted}" font-family="{FONT_BODY}" font-size="13">{escape_xml(subtitle)}</text>')

    # Grid
    for tick in yticks:
        y = yp(tick)
        o.append(f'<line x1="{cl}" y1="{y:.1f}" x2="{cr}" y2="{y:.1f}" stroke="{t.grid}" stroke-width="0.75"/>')
        o.append(f'<text x="{cl - 12}" y="{y + 4:.1f}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="12" text-anchor="end">{fmt(tick)}</text>')

    # Bars
    zy = yp(0)
    for ci, cat in enumerate(cats):
        gx = cl + ci * gw
        cx = gx + gw / 2
        o.append(f'<text x="{cx:.1f}" y="{cb + 28}" fill="{t.text2}" font-family="{FONT_BODY}" font-size="13" text-anchor="middle">{escape_xml(cat)}</text>')

        for si, s in enumerate(series):
            color = bar_colors[si % len(bar_colors)]
            val = s["values"][ci]
            bx = gx + margin + si * (bw + gap)
            by = yp(val)
            bh = abs(zy - by)
            bar_y = min(by, zy)
            o.append(f'<rect x="{bx:.1f}" y="{bar_y:.1f}" width="{bw:.1f}" height="{bh:.1f}" fill="{color}" rx="3"/>')
            o.append(f'<text x="{bx + bw/2:.1f}" y="{bar_y - 8:.1f}" fill="{t.text2}" font-family="{FONT_BODY}" font-size="12" font-weight="600" text-anchor="middle">{fmt(val)}</text>')

    # Legend (top right)
    for si, s in enumerate(series):
        color = bar_colors[si % len(bar_colors)]
        lx = cr - 10
        ly = pad["top"] - 20 - (n_ser - 1 - si) * 22
        o.append(f'<rect x="{lx - 80}" y="{ly - 9}" width="14" height="14" fill="{color}" rx="3"/>')
        o.append(f'<text x="{lx - 60}" y="{ly + 2}" fill="{t.text2}" font-family="{FONT_BODY}" font-size="12">{escape_xml(s["name"])}</text>')

    if source:
        o.append(f'<text x="{pad["left"]}" y="{h - 18}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="10" opacity="0.65">Source: {escape_xml(source)}</text>')

    o.append("</svg>")
    return "\n".join(o)


# ── Chart: Horizontal Bar ──────────────────────────────────────────────────────

def generate_horizontal_bar(data, title, w, h, source, subtitle, theme):
    t = Theme(theme == "dark")
    items = data["items"]
    n = len(items)

    label_width = 180
    pad = {"top": 90, "right": 80, "bottom": 50, "left": label_width + 20}
    cl, cr = pad["left"], w - pad["right"]
    ct, cb = pad["top"], h - pad["bottom"]
    cw, ch = cr - cl, cb - ct

    max_val = max(item["value"] for item in items)
    bh = min(40, ch / n * 0.65)
    spacing = (ch - bh * n) / (n + 1)

    o = []
    o.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}">')
    o.append(f'<rect width="{w}" height="{h}" fill="{t.bg}" rx="12"/>')
    o.append(f'<text x="30" y="38" fill="{t.text1}" font-family="{FONT_TITLE}" font-size="22" font-weight="700">{escape_xml(title)}</text>')
    if subtitle:
        o.append(f'<text x="30" y="60" fill="{t.muted}" font-family="{FONT_BODY}" font-size="13">{escape_xml(subtitle)}</text>')

    for i, item in enumerate(items):
        by = ct + spacing + i * (bh + spacing)
        bw_val = (item["value"] / max_val) * cw
        color = FREQ_PURPLE if i == 0 else MED_GREEN if i == 1 else GRAY_PRIMARY

        # Label
        o.append(f'<text x="{cl - 16}" y="{by + bh/2 + 5:.1f}" fill="{t.text2}" font-family="{FONT_BODY}" font-size="14" text-anchor="end">{escape_xml(item["label"])}</text>')
        # Bar
        o.append(f'<rect x="{cl}" y="{by:.1f}" width="{bw_val:.1f}" height="{bh:.1f}" fill="{color}" rx="4"/>')
        # Value at end of bar
        val_str = fmt(item["value"])
        o.append(f'<text x="{cl + bw_val + 12:.1f}" y="{by + bh/2 + 5:.1f}" fill="{t.text1}" font-family="{FONT_BODY}" font-size="14" font-weight="700">{val_str}</text>')

    if source:
        o.append(f'<text x="30" y="{h - 14}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="10" opacity="0.65">Source: {escape_xml(source)}</text>')

    o.append("</svg>")
    return "\n".join(o)


# ── Chart: Comparison Table ────────────────────────────────────────────────────

def generate_comparison_table(data, title, w, h, source, subtitle, theme):
    """Clean comparison table like the Basquiat vs Banksy milestone table."""
    t = Theme(theme == "dark")
    cols = data["columns"]  # ["", "Basquiat", "Banksy"]
    rows = data["rows"]     # [["First street works", "Late 1970s", "Mid-1990s"], ...]

    n_cols = len(cols)
    n_rows = len(rows)
    pad = {"top": 80, "left": 40, "right": 40}
    table_w = w - pad["left"] - pad["right"]
    col_w = table_w / n_cols
    row_h = 52
    header_h = 48
    table_top = pad["top"]
    table_h = header_h + row_h * n_rows

    # Auto height
    h = max(h, table_top + table_h + 60)

    o = []
    o.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}">')
    o.append(f'<rect width="{w}" height="{h}" fill="{t.card}" rx="12"/>')

    if title:
        o.append(f'<text x="{pad["left"]}" y="45" fill="{t.text1}" font-family="{FONT_TITLE}" font-size="20" font-weight="700">{escape_xml(title)}</text>')

    # Header row
    hy = table_top
    # Header underline (purple accent)
    o.append(f'<line x1="{pad["left"]}" y1="{hy + header_h}" x2="{w - pad["right"]}" y2="{hy + header_h}" stroke="{FREQ_PURPLE}" stroke-width="2"/>')

    for ci, col in enumerate(cols):
        cx = pad["left"] + ci * col_w
        if ci == 0:
            continue  # first col is row labels, no header
        o.append(f'<text x="{cx + col_w/2}" y="{hy + 32}" fill="{t.text1}" font-family="{FONT_BODY}" font-size="15" font-weight="700" text-anchor="middle">{escape_xml(col)}</text>')

    # Data rows
    for ri, row in enumerate(rows):
        ry = table_top + header_h + ri * row_h
        # Row separator
        if ri > 0:
            o.append(f'<line x1="{pad["left"]}" y1="{ry}" x2="{w - pad["right"]}" y2="{ry}" stroke="{t.grid}" stroke-width="0.75"/>')

        for ci, cell in enumerate(row):
            cx = pad["left"] + ci * col_w
            if ci == 0:
                # Row label (muted)
                o.append(f'<text x="{cx + 8}" y="{ry + 33}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="14">{escape_xml(cell)}</text>')
            else:
                # Data cell (bold)
                o.append(f'<text x="{cx + col_w/2}" y="{ry + 33}" fill="{t.text1}" font-family="{FONT_BODY}" font-size="14" font-weight="600" text-anchor="middle">{escape_xml(cell)}</text>')

    if source:
        o.append(f'<text x="{pad["left"]}" y="{h - 14}" fill="{t.muted}" font-family="{FONT_BODY}" font-size="10" opacity="0.65">{escape_xml(source)}</text>')

    o.append("</svg>")
    return "\n".join(o)


# ── Social Card ────────────────────────────────────────────────────────────────

def generate_social_card(title, data, w=1080, h=1080):
    stat_value = data.get("stat_value", "")
    stat_label = data.get("stat_label", "")
    body_lines = data.get("body", [])

    # Investor book design system fonts
    f_display = "Cormorant Garamond, Georgia, Times New Roman, serif"
    f_sans = "Inter, Helvetica Neue, Helvetica, Arial, sans-serif"
    f_body = "DM Sans, Helvetica Neue, Helvetica, Arial, sans-serif"

    # Investor book colors
    bg = "#ffffff"
    off_white = "#f7f5f1"
    warm_gray = "#e8e4de"
    text_primary = "#1a1a1a"
    text_body = "#444444"
    text_muted = "#777777"
    mid_gray = "#888888"
    accent = "#495DE5"

    margin = 90

    o = []
    o.append(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}">')
    o.append(f'<rect width="{w}" height="{h}" fill="{bg}"/>')

    # Thin rule at top
    o.append(f'<rect x="{margin}" y="80" width="60" height="2" fill="{text_primary}"/>')

    # Title in Cormorant Garamond italic
    words = title.split()
    lines = []
    cur = ""
    for word in words:
        test = (cur + " " + word).strip()
        if len(test) > 22 and cur:
            lines.append(cur)
            cur = word
        else:
            cur = test
    if cur:
        lines.append(cur)

    ty = 160
    for i, line in enumerate(lines):
        o.append(f'<text x="{margin}" y="{ty + i * 58}" fill="{text_primary}" font-family="{f_display}" font-size="46" font-style="italic" font-weight="400">{escape_xml(line)}</text>')

    # Stat card (off-white background box with stat inside)
    card_y = ty + len(lines) * 58 + 50
    card_h = 240
    card_w = w - margin * 2
    o.append(f'<rect x="{margin}" y="{card_y}" width="{card_w}" height="{card_h}" fill="{off_white}" stroke="{warm_gray}" stroke-width="1" rx="0"/>')

    # Stat number inside card
    o.append(f'<text x="{margin + 40}" y="{card_y + 80}" fill="{text_primary}" font-family="{f_sans}" font-size="72" font-weight="800">{escape_xml(stat_value)}</text>')

    # Stat label inside card
    label_words = stat_label.split()
    label_lines = []
    cur = ""
    for word in label_words:
        test = (cur + " " + word).strip()
        if len(test) > 45 and cur:
            label_lines.append(cur)
            cur = word
        else:
            cur = test
    if cur:
        label_lines.append(cur)

    for i, ll in enumerate(label_lines):
        o.append(f'<text x="{margin + 40}" y="{card_y + 120 + i * 28}" fill="{text_body}" font-family="{f_body}" font-size="22">{escape_xml(ll)}</text>')

    # Optional body text below the card
    if body_lines:
        body_y = card_y + card_h + 50
        for bl in body_lines:
            o.append(f'<text x="{margin}" y="{body_y}" fill="{text_muted}" font-family="{f_body}" font-size="22">{escape_xml(bl)}</text>')
            body_y += 34

    # Page number / branding at bottom
    o.append(f'<text x="{w / 2}" y="{h - 50}" fill="{mid_gray}" font-family="{f_sans}" font-size="9" font-weight="600" letter-spacing="5" text-anchor="middle">MASTERWORKS ACADEMY</text>')

    o.append("</svg>")
    return "\n".join(o)


# ── Main ───────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description="Masterworks SVG chart generator v2")
    parser.add_argument("--type", required=True, choices=["line", "bar", "horizontal-bar", "comparison-table", "social-card"])
    parser.add_argument("--title", required=True)
    parser.add_argument("--data", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--width", type=int, default=None)
    parser.add_argument("--height", type=int, default=None)
    parser.add_argument("--source", default=None)
    parser.add_argument("--subtitle", default=None)
    parser.add_argument("--theme", default="light", choices=["light", "dark"])
    args = parser.parse_args()

    data = json.loads(args.data)
    w = args.width or (1080 if args.type == "social-card" else DEFAULT_WIDTH)
    h = args.height or (1080 if args.type == "social-card" else DEFAULT_HEIGHT)

    if args.type == "line":
        svg = generate_line_chart(data, args.title, w, h, args.source, args.subtitle, args.theme)
    elif args.type == "bar":
        svg = generate_bar_chart(data, args.title, w, h, args.source, args.subtitle, args.theme)
    elif args.type == "horizontal-bar":
        svg = generate_horizontal_bar(data, args.title, w, h, args.source, args.subtitle, args.theme)
    elif args.type == "comparison-table":
        svg = generate_comparison_table(data, args.title, w, h, args.source, args.subtitle, args.theme)
    elif args.type == "social-card":
        svg = generate_social_card(args.title, data, w, h)

    os.makedirs(os.path.dirname(args.output) or ".", exist_ok=True)
    with open(args.output, "w") as f:
        f.write(svg)
    print(f"Generated: {args.output} ({w}x{h})")


if __name__ == "__main__":
    main()
