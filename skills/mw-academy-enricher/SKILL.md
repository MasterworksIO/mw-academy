---
name: mw-academy-enricher
description: >
  Enrich existing Masterworks Academy article drafts with internal data, visualizations, and social media graphics. Use this skill when the user asks to "enrich", "add data to", "add charts to", "visualize", "create graphics for", or "phase two" an Academy draft article. Also triggers on "pull internal data for" or "make infographics for" an article. Works on any markdown article in content/drafts/.
---

# Masterworks Academy Article Enricher

You take an existing article draft and make it better by adding internal Masterworks data, producing SVG charts that can be edited in Figma, and generating social media graphics.

## Input

The user provides one of:
- A filename or slug from `content/drafts/` (e.g., "the freeports article" or "how-asian-demand-influences-western-art-prices")
- A topic name that maps to an existing draft

## Brand Palette

Every visual output uses this palette. No exceptions.

| Token | Hex | Usage |
|-------|-----|-------|
| Background | `#131217` | Chart backgrounds, card backgrounds |
| Night | `#0A0A0D` | Darker variant for contrast, borders |
| Dusk Purple | `#13134A` | Secondary fills, area chart underlays |
| Frequency Purple | `#3838E6` | Primary data line, primary bar color, accents |
| Medium Green | `#24CB71` | Secondary data line, positive indicators, comparison series |
| White | `#FFFFFF` | Titles, primary labels |
| Frost | `#E8E8E8` | Body text, axis labels, secondary labels |

Fonts: Tiempos Headline for chart titles. Neue Haas Grotesk (or fallback: Helvetica, Arial) for axis labels, data labels, and body text.

Dark backgrounds are the default. All charts use `#131217` or `#0A0A0D` as the background color. Data sits on dark, labels are light.

## Stage 1: Parse the Draft

Read the markdown file from `content/drafts/`. Build a mental list of enrichment targets:

**Explicit flags** — look for these exact strings:
- `[NEEDS INTERNAL DATA]` — can be replaced with real Masterworks data
- `[NEEDS UPDATED DATA]` — needs a fresher number
- `[NEEDS INTERNAL REVIEW]` — leave in place, annotate with findings

**Artist mentions** — extract every artist name in the article. For each one, check if Masterworks tracks them by invoking the `mw-artist` skill. Note which artists have data available.

**Chart opportunities** — identify sections where a visualization would make the content stronger. Look for:
- Sentences with multiple dollar figures or percentages that could be shown as a bar chart
- Time-series data (year-over-year figures) that could be a line chart
- Comparisons between artists, segments, or asset classes that could be grouped bars or a comparison table
- Geographic data that could be shown as a labeled map or table

For each chart opportunity, note: the section it belongs to, the data it would show, and what chart type fits best.

## Stage 2: Fetch Internal Data

For each artist found in Stage 1, invoke the `mw-artist` skill:

```
/skill mw-artist [artist-id]
```

This returns: basic profile, exhibition history, and sales data (auction records, price trends, volume).

Store the results. You will use them in both Stage 3 (text enrichment) and Stage 4 (visualization).

If the article covers market-level data (segment returns, index comparisons) rather than specific artists, check whether the mw-org-mcp GraphQL tools have relevant queries for segment or index data.

If a flag asks for data that the internal tools cannot provide, leave the flag in place and add a note: `[STILL NEEDS INTERNAL DATA: {what's missing and why}]`.

## Stage 3: Enrich the Article Text

For each `[NEEDS INTERNAL DATA]` flag, write a replacement using the fetched data. Follow every style rule from the writer skill at `skills/mw-academy-writer/SKILL.md`:
- No em dashes or en dashes
- No banned AI trope words or sentence patterns
- Plain Saxon words
- Only recent sources

For `[NEEDS UPDATED DATA]` flags, replace with the freshest number from the internal data.

For `[NEEDS INTERNAL REVIEW]` flags, add an annotation below the flag with what you found, but keep the flag itself.

Save the enriched article to:
```
content/enriched/{slug}.md
```

The enriched version should be a complete, publication-ready article. Do not leave any orphan flags unless the data truly could not be found.

## Stage 4: Generate SVG Visualizations

For each chart opportunity identified in Stage 1, produce an SVG file.

Use the helper script at `skills/mw-academy-enricher/scripts/generate_chart.py`. Call it via Bash:

```bash
python3 skills/mw-academy-enricher/scripts/generate_chart.py \
  --type line \
  --title "Artist Price Trajectory" \
  --data '{"series":[{"name":"Basquiat","values":[{"year":2016,"value":100},{"year":2017,"value":245}]}]}' \
  --output content/visualizations/{slug}/chart-name.svg
```

The script supports these chart types:
- `line` — time-series line chart (one or more series)
- `bar` — vertical grouped bar chart
- `horizontal-bar` — horizontal bar chart (good for comparisons)
- `comparison` — side-by-side metric comparison (like the Banksy vs. Basquiat panels)

All charts are generated at 1200x700 by default with the brand palette baked in.

Save all SVGs to:
```
content/visualizations/{article-slug}/
```

Name them descriptively: `price-trajectory-basquiat.svg`, `segment-returns-comparison.svg`, etc.

### Chart Design Guidelines

The goal is clean, readable charts that look like they belong in a Bloomberg terminal or a Masterworks investor report. Not flashy, not decorative.

- Grid lines: thin, `#13134A` (Dusk Purple), subtle
- Axis labels: `#E8E8E8` (Frost), 12px Neue Haas Grotesk
- Chart title: `#FFFFFF` (White), 18px Tiempos Headline, top-left aligned
- Data lines: 2.5px stroke, `#3838E6` (Frequency Purple) for primary, `#24CB71` (Medium Green) for secondary
- Bar fills: same colors as lines, with 80% opacity
- Source attribution: small text at bottom-left, `#E8E8E8`, 10px

## Stage 5: Generate Social Media Graphics

For each article, produce at minimum:

**OG Image** (1200x630): Article title in White on Background, one key data point in Frequency Purple, Masterworks wordmark in Frost at bottom.

**Instagram Carousel** (1080x1080, 3-5 slides):
- Slide 1: Title card with article headline
- Slides 2-4: Key data points or mini-charts (simplified versions of the article visualizations)
- Final slide: CTA ("Read more at masterworks.com/academy") with Masterworks branding

Save to:
```
content/social/{article-slug}/
```

These are SVGs so the design team can open them in Figma, tweak, and export to PNG.

## Output Summary

After completing all stages, report:
- How many flags were resolved vs. still pending
- How many SVG charts were generated and their filenames
- How many social graphics were produced
- The file paths for all outputs

Then post a summary to `#mw-academy` Slack (channel ID: `C0ARA4CQRQC`):

```
*Article Enriched:* [Title]

Added [N] internal data points, generated [N] SVG charts and [N] social graphics.
[N] flags resolved, [N] still pending.

📊 Visualizations: content/visualizations/{slug}/
📱 Social: content/social/{slug}/
📄 Enriched draft: content/enriched/{slug}.md
```
