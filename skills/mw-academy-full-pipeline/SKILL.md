---
name: mw-academy-full-pipeline
description: >
  Run the complete Masterworks Academy production pipeline for a single article topic, end to end: research and write the draft, generate article visualizations, build the designed SVG pages (Figma-ready), build the social media package (SVGs + copy), commit everything to the academy-platform branch, and post a summary to the #mw-academy Slack channel. Use whenever the user says "full pipeline for [topic]", "produce [topic] end to end", "do the whole thing for [topic]", "all the way through for [topic]", or any variant that asks for draft plus visuals plus social for a single article in one shot.
---

# Masterworks Academy Full Pipeline

Run the entire article production workflow for one topic in one go. Output is a draft, designed pages, charts, social package, a git commit, a push, and a Slack summary.

## Input

The user provides a topic title (e.g., "Dollar Strength and Art Prices: Why Currency Matters at Auction"). That is all.

## Prerequisites

Confirm these exist before starting:
- `/Users/jacknorman/Desktop/claudeprojects/academy/.env` contains `PERPLEXITY_API_KEY`
- Working tree on `academy-platform` branch
- The writer and enricher skills are installed:
  - `skills/mw-academy-writer/SKILL.md`
  - `skills/mw-academy-enricher/SKILL.md`
  - `skills/mw-academy-enricher/scripts/generate_chart.py`
- Slack channel `#mw-academy` is known: channel ID `C0ARA4CQRQC`

## Pipeline (5 phases)

Compute a slug from the topic title: lowercase, replace spaces with hyphens, remove punctuation. Example: "Dollar Strength and Art Prices: Why Currency Matters at Auction" → `dollar-strength-and-art-prices-why-currency-matters-at-auction`.

Paths used below:
- `DRAFT = content/drafts/{slug}.md`
- `CHARTS_DIR = content/visualizations/{slug}/`
- `PAGES_DIR = content/enriched/{slug}-pages/`
- `SOCIAL_DIR = content/social/{slug}/`

### Phase 1: Write the draft

Invoke the writer skill with the topic title. It researches via Perplexity + WebSearch and saves the draft to `DRAFT` following all style rules (no dashes, no AI tropes, recent sources only, plain words).

Do not proceed to phase 2 until the draft file exists on disk.

### Phase 2: Run three enrichment tasks in parallel

Launch three background subagents simultaneously. They do not depend on each other, only on the draft from phase 1.

**Agent A: Article visualizations (light theme SVGs).** Uses `skills/mw-academy-enricher/scripts/generate_chart.py`. Produces 3-4 charts based on the article's strongest data points. Saves to `CHARTS_DIR`. Chart types: line, bar, horizontal-bar, comparison-table. The script's defaults already produce the investor book design language (white background, Inter for stats, Cormorant Garamond for titles where applicable, warm-gray borders, #495DE5 accent used sparingly).

**Agent B: Designed article pages (Figma-editable SVGs).** Builds 7-9 SVG files at 816x1056, one per page. Uses the investor book design system explicitly: Inter for caps/stats/subheadings, Cormorant Garamond for italic display titles and pull quotes, DM Sans for body text. Palette: white backgrounds, off-white (#f7f5f1) stat cards with warm-gray (#e8e4de) borders, dark (#0f0f0f) pages for contrast, text-primary #1a1a1a, text-body #444444, text-muted #777777, accent #495DE5. Consistent margins: 72px sides, 72px top, 60px bottom. Page numbers bottom right. Include title page, body sections with appropriate stat cards / pull quotes / bar charts, at least one dark contrast page, a bottom-line page, and a FAQ page. **Do not include a sources page.** Use the logo at `/Users/jacknorman/Desktop/claudeprojects/academy/assets/logos/logo-alt.png` on the title page where branding is needed (embed as base64 or reference path). Saves to `PAGES_DIR`.

**Agent C: Social package.** Generates 5 Instagram carousel SVGs (1080x1080) + 1 OG image (1200x630) using `generate_chart.py --type social-card`. The script already produces the light investor book social design (white background, Cormorant italic titles, off-white stat card, Inter for stats, DM Sans for labels, MASTERWORKS ACADEMY small caps at bottom). Also writes `copy.md` with platform-native copy for Instagram (2nd person, 15-20 hashtags, disclosure), LinkedIn (1st person, discussion question, 3-5 hashtags), and Facebook (conversational, short, link). Follows compliance rules from `/Users/jacknorman/Desktop/claudeprojects/AutoMarketer/agents/social-media-transformer/compliance-layer.md`: Masterworks in bottom 30-40%, no em dashes, "artworks" not "paintings", "invest in" not "buy", standard Reg A disclosure on every post. Saves to `SOCIAL_DIR`.

Wait for all three agents to complete before proceeding.

### Phase 3: Verify output

Confirm each directory contains files before committing:
- `DRAFT` exists and is > 1000 words
- `CHARTS_DIR` contains 3-4 SVG files
- `PAGES_DIR` contains 7-9 SVG files (page-NN-*.svg pattern)
- `SOCIAL_DIR` contains 6 SVG files + `copy.md`

If any directory is missing files, re-launch the relevant agent. Do not proceed with an incomplete package.

### Phase 4: Commit and push

Run via Bash:

```bash
git add \
  content/drafts/{slug}.md \
  content/visualizations/{slug}/ \
  content/enriched/{slug}-pages/ \
  content/social/{slug}/
git commit -m "Add full production package: {Topic Title}

Complete end-to-end output for one article:

Draft: (word count), (source count) sources (year range)

Designed pages: (N) SVGs (816x1056) for Figma with investor book
design system, consistent margins, stat cards, pull quotes

Article charts: (N) SVGs (light theme)

Social package: 5 carousel SVGs + OG image + copy.md for
Instagram/LinkedIn/Facebook

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>"
git push origin academy-platform
```

### Phase 5: Post summary to Slack

Use `slack_send_message` to channel ID `C0ARA4CQRQC` with this format:

```
*Full Production Package Ready:* {Topic Title}

End-to-end output, four deliverables:

*1. Draft* (~{N} words, {N} sources)
{2-3 sentence summary of the article}

*2. Designed Article* ({N} SVG pages for Figma)
Investor book design system. Stat cards, pull quotes, dark contrast page, bar charts, consistent margins. No sources page.

*3. Article Charts* ({N} SVGs, light theme)
{Brief list of chart topics, comma-separated}

*4. Social Package* (6 SVGs + copy)
Light investor book design. Instagram/LinkedIn/Facebook copy, compliance-checked.

:page_facing_up: `content/drafts/{slug}.md`
:triangular_ruler: `content/enriched/{slug}-pages/`
:bar_chart: `content/visualizations/{slug}/`
:iphone: `content/social/{slug}/`
```

## Error handling

If the draft phase fails (Perplexity API error, malformed output), stop and report the error. Do not proceed to phase 2 with a broken draft.

If any phase-2 agent fails, re-run just that agent. Do not re-run the whole pipeline.

If the git commit fails (conflict, permissions), surface the git error and let the user resolve. Do not force push.

If Slack posting fails (permission denied), report the git commit succeeded and include the Slack message text so the user can post manually.

## Notes on quality

The writer skill and enricher skill have their own strict style rules (no em dashes, no AI tropes, recent sources, Saxon words, investor book design language). This pipeline does not relax those rules. Every output respects them.

The designed SVG pages are intended to be imported into Figma for final polish. They are not the final published assets, they are the starting point.

The draft is also a starting point. In a real workflow the editor reviews and tweaks the draft before the team produces the rest of the materials. This pipeline produces everything at once for speed, assuming the draft is "good enough" as a starting point.
