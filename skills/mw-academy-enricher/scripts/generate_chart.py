#!/usr/bin/env python3
"""
Masterworks Academy SVG Chart Generator

Produces Figma-ready SVG charts using the Masterworks brand palette.
No external dependencies — uses only Python stdlib.

Usage:
    python3 generate_chart.py --type line --title "Chart Title" --data '{"series":[...]}' --output out.svg
    python3 generate_chart.py --type bar --title "Chart Title" --data '{"categories":[...]}' --output out.svg
    python3 generate_chart.py --type horizontal-bar --title "..." --data '{"items":[...]}' --output out.svg

Data formats:
    line:  {"series": [{"name": "S1", "values": [{"year": 2020, "value": 100}, ...]}, ...]}
    bar:   {"categories": ["Cat1", "Cat2"], "series": [{"name": "S1", "values": [10, 20]}, ...]}
    horizontal-bar: {"items": [{"label": "Item1", "value": 85}, {"label": "Item2", "value": 60}, ...]}
"""

import argparse
import json
import math
import os
import sys

# Brand palette
BACKGROUND = "#131217"
NIGHT = "#0A0A0D"
DUSK_PURPLE = "#13134A"
FREQ_PURPLE = "#3838E6"
MED_GREEN = "#24CB71"
WHITE = "#FFFFFF"
FROST = "#E8E8E8"

SERIES_COLORS = [FREQ_PURPLE, MED_GREEN, "#A78BFA", "#F59E0B", "#EC4899"]

# Chart dimensions
DEFAULT_WIDTH = 1200
DEFAULT_HEIGHT = 700
PADDING = {"top": 70, "right": 60, "bottom": 70, "left": 80}


def escape_xml(s):
    return str(s).replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace('"', "&quot;")


def nice_ticks(min_val, max_val, target_count=5):
    """Generate nice round tick values for an axis."""
    if min_val == max_val:
        return [min_val]
    raw_step = (max_val - min_val) / target_count
    magnitude = 10 ** math.floor(math.log10(raw_step))
    residual = raw_step / magnitude
    if residual <= 1.5:
        nice_step = 1 * magnitude
    elif residual <= 3:
        nice_step = 2 * magnitude
    elif residual <= 7:
        nice_step = 5 * magnitude
    else:
        nice_step = 10 * magnitude
    start = math.floor(min_val / nice_step) * nice_step
    ticks = []
    val = start
    while val <= max_val + nice_step * 0.5:
        ticks.append(val)
        val += nice_step
    return ticks


def format_value(v):
    """Format a numeric value for display."""
    if abs(v) >= 1_000_000_000:
        return f"${v / 1_000_000_000:.1f}B"
    if abs(v) >= 1_000_000:
        return f"${v / 1_000_000:.1f}M"
    if abs(v) >= 1_000:
        return f"${v / 1_000:.0f}K" if v == int(v) else f"{v:,.0f}"
    if v == int(v):
        return str(int(v))
    return f"{v:.1f}"


def svg_header(width, height, title, source=None):
    """Generate SVG header with background and title."""
    lines = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}" width="{width}" height="{height}">',
        f'  <rect width="{width}" height="{height}" fill="{BACKGROUND}"/>',
        f'  <text x="{PADDING["left"]}" y="40" fill="{WHITE}" font-family="Tiempos Headline, Georgia, serif" font-size="20" font-weight="600">{escape_xml(title)}</text>',
    ]
    if source:
        lines.append(
            f'  <text x="{PADDING["left"]}" y="{height - 15}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="10" opacity="0.7">Source: {escape_xml(source)}</text>'
        )
    return "\n".join(lines)


def svg_footer():
    return "</svg>"


def generate_line_chart(data, title, width, height, source=None):
    """Generate a line chart SVG."""
    series_list = data["series"]
    chart_left = PADDING["left"]
    chart_right = width - PADDING["right"]
    chart_top = PADDING["top"]
    chart_bottom = height - PADDING["bottom"]
    chart_w = chart_right - chart_left
    chart_h = chart_bottom - chart_top

    # Collect all values for axis scaling
    all_years = []
    all_values = []
    for s in series_list:
        for pt in s["values"]:
            all_years.append(pt["year"])
            all_values.append(pt["value"])

    min_year, max_year = min(all_years), max(all_years)
    min_val = min(0, min(all_values))
    max_val = max(all_values) * 1.1

    y_ticks = nice_ticks(min_val, max_val, 5)
    if y_ticks:
        max_val = max(y_ticks)
        min_val = min(y_ticks)

    def x_pos(year):
        if max_year == min_year:
            return chart_left + chart_w / 2
        return chart_left + (year - min_year) / (max_year - min_year) * chart_w

    def y_pos(val):
        if max_val == min_val:
            return chart_top + chart_h / 2
        return chart_bottom - (val - min_val) / (max_val - min_val) * chart_h

    parts = [svg_header(width, height, title, source)]

    # Grid lines
    for tick in y_ticks:
        y = y_pos(tick)
        parts.append(f'  <line x1="{chart_left}" y1="{y:.1f}" x2="{chart_right}" y2="{y:.1f}" stroke="{DUSK_PURPLE}" stroke-width="1"/>')
        parts.append(f'  <text x="{chart_left - 10}" y="{y + 4:.1f}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="12" text-anchor="end">{format_value(tick)}</text>')

    # X-axis labels
    year_range = max_year - min_year
    step = max(1, year_range // 8)
    for year in range(min_year, max_year + 1, step):
        x = x_pos(year)
        parts.append(f'  <text x="{x:.1f}" y="{chart_bottom + 25}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="12" text-anchor="middle">{year}</text>')

    # Data lines
    for i, s in enumerate(series_list):
        color = SERIES_COLORS[i % len(SERIES_COLORS)]
        points = sorted(s["values"], key=lambda p: p["year"])
        path_parts = []
        for j, pt in enumerate(points):
            x = x_pos(pt["year"])
            y = y_pos(pt["value"])
            if j == 0:
                path_parts.append(f"M {x:.1f} {y:.1f}")
            else:
                path_parts.append(f"L {x:.1f} {y:.1f}")
        path_d = " ".join(path_parts)
        parts.append(f'  <path d="{path_d}" fill="none" stroke="{color}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>')

        # End label
        last = points[-1]
        lx = x_pos(last["year"])
        ly = y_pos(last["value"])
        parts.append(f'  <circle cx="{lx:.1f}" cy="{ly:.1f}" r="4" fill="{color}"/>')
        parts.append(f'  <text x="{lx + 10:.1f}" y="{ly + 4:.1f}" fill="{color}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="12" font-weight="600">{escape_xml(s["name"])}</text>')

    parts.append(svg_footer())
    return "\n".join(parts)


def generate_bar_chart(data, title, width, height, source=None):
    """Generate a grouped vertical bar chart SVG."""
    categories = data["categories"]
    series_list = data["series"]
    chart_left = PADDING["left"]
    chart_right = width - PADDING["right"]
    chart_top = PADDING["top"]
    chart_bottom = height - PADDING["bottom"]
    chart_w = chart_right - chart_left
    chart_h = chart_bottom - chart_top

    all_values = [v for s in series_list for v in s["values"]]
    min_val = min(0, min(all_values))
    max_val = max(all_values) * 1.15

    y_ticks = nice_ticks(min_val, max_val, 5)
    if y_ticks:
        max_val = max(y_ticks)
        min_val = min(y_ticks)

    def y_pos(val):
        if max_val == min_val:
            return chart_top + chart_h / 2
        return chart_bottom - (val - min_val) / (max_val - min_val) * chart_h

    n_cats = len(categories)
    n_series = len(series_list)
    group_width = chart_w / n_cats
    bar_gap = group_width * 0.15
    bar_area = group_width - bar_gap * 2
    bar_width = bar_area / n_series
    inner_gap = bar_width * 0.1
    bar_width -= inner_gap

    parts = [svg_header(width, height, title, source)]

    # Grid lines
    for tick in y_ticks:
        y = y_pos(tick)
        parts.append(f'  <line x1="{chart_left}" y1="{y:.1f}" x2="{chart_right}" y2="{y:.1f}" stroke="{DUSK_PURPLE}" stroke-width="1"/>')
        parts.append(f'  <text x="{chart_left - 10}" y="{y + 4:.1f}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="12" text-anchor="end">{format_value(tick)}</text>')

    # Bars
    zero_y = y_pos(0)
    for ci, cat in enumerate(categories):
        group_x = chart_left + ci * group_width
        # Category label
        cx = group_x + group_width / 2
        parts.append(f'  <text x="{cx:.1f}" y="{chart_bottom + 25}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="12" text-anchor="middle">{escape_xml(cat)}</text>')

        for si, s in enumerate(series_list):
            color = SERIES_COLORS[si % len(SERIES_COLORS)]
            val = s["values"][ci]
            bx = group_x + bar_gap + si * (bar_width + inner_gap)
            by = y_pos(val)
            bh = abs(zero_y - by)
            bar_y = min(by, zero_y)
            parts.append(f'  <rect x="{bx:.1f}" y="{bar_y:.1f}" width="{bar_width:.1f}" height="{bh:.1f}" fill="{color}" opacity="0.85" rx="2"/>')
            # Value label on top
            parts.append(f'  <text x="{bx + bar_width / 2:.1f}" y="{bar_y - 6:.1f}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="11" text-anchor="middle">{format_value(val)}</text>')

    # Legend
    legend_x = chart_right - 20
    for si, s in enumerate(series_list):
        color = SERIES_COLORS[si % len(SERIES_COLORS)]
        ly = chart_top + si * 22
        parts.append(f'  <rect x="{legend_x - 80}" y="{ly - 8}" width="12" height="12" fill="{color}" rx="2"/>')
        parts.append(f'  <text x="{legend_x - 62}" y="{ly + 2}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="12">{escape_xml(s["name"])}</text>')

    parts.append(svg_footer())
    return "\n".join(parts)


def generate_horizontal_bar_chart(data, title, width, height, source=None):
    """Generate a horizontal bar chart SVG."""
    items = data["items"]
    chart_left = PADDING["left"] + 100  # Extra room for labels
    chart_right = width - PADDING["right"]
    chart_top = PADDING["top"]
    chart_bottom = height - PADDING["bottom"]
    chart_w = chart_right - chart_left
    chart_h = chart_bottom - chart_top

    n_items = len(items)
    max_val = max(item["value"] for item in items) * 1.1
    bar_height = min(35, chart_h / n_items * 0.7)
    bar_gap = (chart_h - bar_height * n_items) / (n_items + 1)

    parts = [svg_header(width, height, title, source)]

    for i, item in enumerate(items):
        by = chart_top + bar_gap + i * (bar_height + bar_gap)
        bw = (item["value"] / max_val) * chart_w
        color = FREQ_PURPLE if i % 2 == 0 else MED_GREEN

        # Label
        parts.append(f'  <text x="{chart_left - 10}" y="{by + bar_height / 2 + 4:.1f}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="13" text-anchor="end">{escape_xml(item["label"])}</text>')
        # Bar
        parts.append(f'  <rect x="{chart_left}" y="{by:.1f}" width="{bw:.1f}" height="{bar_height:.1f}" fill="{color}" opacity="0.85" rx="3"/>')
        # Value
        parts.append(f'  <text x="{chart_left + bw + 8:.1f}" y="{by + bar_height / 2 + 4:.1f}" fill="{WHITE}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="13" font-weight="600">{format_value(item["value"])}</text>')

    parts.append(svg_footer())
    return "\n".join(parts)


def generate_social_card(title, stat_label, stat_value, width=1080, height=1080):
    """Generate a social media card SVG."""
    parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}" width="{width}" height="{height}">',
        f'  <rect width="{width}" height="{height}" fill="{BACKGROUND}"/>',
        # Top accent line
        f'  <rect x="80" y="80" width="120" height="4" fill="{FREQ_PURPLE}" rx="2"/>',
        # Title
        f'  <text x="80" y="180" fill="{WHITE}" font-family="Tiempos Headline, Georgia, serif" font-size="42" font-weight="600">',
    ]
    # Word-wrap title (rough, ~25 chars per line at this size)
    words = title.split()
    lines = []
    current = ""
    for w in words:
        if len(current + " " + w) > 28 and current:
            lines.append(current)
            current = w
        else:
            current = (current + " " + w).strip()
    if current:
        lines.append(current)

    for j, line in enumerate(lines):
        dy = "0" if j == 0 else "52"
        parts.append(f'    <tspan x="80" dy="{dy}">{escape_xml(line)}</tspan>')
    parts.append("  </text>")

    # Big stat
    stat_y = 180 + len(lines) * 52 + 120
    parts.append(f'  <text x="80" y="{stat_y}" fill="{FREQ_PURPLE}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="72" font-weight="700">{escape_xml(stat_value)}</text>')
    parts.append(f'  <text x="80" y="{stat_y + 45}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="20">{escape_xml(stat_label)}</text>')

    # Bottom branding
    parts.append(f'  <text x="80" y="{height - 60}" fill="{FROST}" font-family="Neue Haas Grotesk, Helvetica, Arial, sans-serif" font-size="14" opacity="0.6">Masterworks Academy</text>')
    parts.append(f'  <rect x="80" y="{height - 45}" width="80" height="3" fill="{FREQ_PURPLE}" rx="1.5"/>')

    parts.append(svg_footer())
    return "\n".join(parts)


def main():
    parser = argparse.ArgumentParser(description="Generate Masterworks-branded SVG charts")
    parser.add_argument("--type", required=True, choices=["line", "bar", "horizontal-bar", "social-card"])
    parser.add_argument("--title", required=True)
    parser.add_argument("--data", required=True, help="JSON string with chart data")
    parser.add_argument("--output", required=True, help="Output SVG file path")
    parser.add_argument("--width", type=int, default=None)
    parser.add_argument("--height", type=int, default=None)
    parser.add_argument("--source", default=None, help="Source attribution text")
    args = parser.parse_args()

    data = json.loads(args.data)

    w = args.width or (1080 if args.type == "social-card" else DEFAULT_WIDTH)
    h = args.height or (1080 if args.type == "social-card" else DEFAULT_HEIGHT)

    if args.type == "line":
        svg = generate_line_chart(data, args.title, w, h, args.source)
    elif args.type == "bar":
        svg = generate_bar_chart(data, args.title, w, h, args.source)
    elif args.type == "horizontal-bar":
        svg = generate_horizontal_bar_chart(data, args.title, w, h, args.source)
    elif args.type == "social-card":
        svg = generate_social_card(args.title, data.get("stat_label", ""), data.get("stat_value", ""), w, h)

    os.makedirs(os.path.dirname(args.output) or ".", exist_ok=True)
    with open(args.output, "w") as f:
        f.write(svg)

    print(f"Generated: {args.output} ({w}x{h})")


if __name__ == "__main__":
    main()
