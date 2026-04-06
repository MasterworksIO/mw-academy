#!/usr/bin/env bash
# =============================================================================
# Local WordPress Setup — Activates theme, plugins, creates sample content
# Run from the academy/ project directory with Docker running
# =============================================================================
set -euo pipefail

CONTAINER="academy-wordpress-1"

# Helper to run WP-CLI inside the container
wpcli() {
    docker exec -u www-data "$CONTAINER" wp "$@" --allow-root 2>/dev/null || \
    docker exec "$CONTAINER" wp "$@" --allow-root
}

echo "=================================="
echo "  Masterworks Academy Local Setup"
echo "=================================="

# ----- Wait for WordPress to be ready -----
echo ""
echo "[1/9] Waiting for WordPress..."
for i in $(seq 1 30); do
    if docker exec "$CONTAINER" wp core is-installed --allow-root 2>/dev/null; then
        echo "  WordPress is ready."
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "  ERROR: WordPress not ready after 30 seconds."
        echo "  Make sure you ran: docker compose up -d"
        echo "  And completed the install wizard at http://localhost:8080"
        exit 1
    fi
    sleep 1
done

# ----- Activate theme -----
echo ""
echo "[2/9] Activating theme and plugins..."
wpcli theme activate masterworks-academy || echo "  Theme activation failed — may need manual activation"

wpcli plugin activate mw-content-types 2>/dev/null || true
wpcli plugin activate mw-data-visualizations 2>/dev/null || true
wpcli plugin activate mw-contentful-integration 2>/dev/null || true
wpcli plugin activate mw-seo-schema 2>/dev/null || true
echo "  Done."

# ----- Configure WordPress settings -----
echo ""
echo "[3/9] Configuring WordPress settings..."
wpcli option update blogname "Masterworks Academy"
wpcli option update blogdescription "Research, Data & Insights for the Art Market"
wpcli option update timezone_string "America/New_York"
wpcli option update date_format "F j, Y"
wpcli option update default_comment_status "closed"
wpcli option update default_ping_status "closed"
wpcli rewrite structure '/%postname%/' --hard
wpcli rewrite flush --hard
echo "  Done."

# ----- Create taxonomy terms -----
echo ""
echo "[4/9] Creating taxonomy terms..."

# Content Pillars
for term in "Research" "Data & Indices" "Opinions & Explainers" "Daily News" "Cultural Updates"; do
    wpcli term create content-pillar "$term" 2>/dev/null || true
done

# Audiences
for term in "Existing Investors" "Prospective Investors" "Financial Advisors" "Media & Analysts"; do
    wpcli term create audience "$term" 2>/dev/null || true
done

# Art Segments
for term in "Contemporary" "Post-War" "Ultra-Contemporary" "Impressionist" "Old Masters" "Photography" "Emerging"; do
    wpcli term create art-segment "$term" 2>/dev/null || true
done

# Artists
for term in "Jean-Michel Basquiat" "Banksy" "Yayoi Kusama" "Yoshitomo Nara" "George Condo" "KAWS" "Keith Haring" "Cecily Brown" "Julie Mehretu" "Flora Yukhnovich"; do
    wpcli term create artist-name "$term" 2>/dev/null || true
done

# Expert Voices
for term in "Scott Lynn" "Research Team" "Chief Art Advisor"; do
    wpcli term create expert-voice "$term" 2>/dev/null || true
done
echo "  Done."

# ----- Create pages -----
echo ""
echo "[5/9] Creating pages..."

HOME_ID=$(wpcli post create --post_type=page --post_title="Academy Home" --post_status=publish --post_name="academy-home" --page_template="page-academy-home.php" --porcelain 2>/dev/null || echo "")
if [ -n "$HOME_ID" ]; then
    wpcli option update show_on_front page
    wpcli option update page_on_front "$HOME_ID"
    echo "  Created Academy Home (ID: $HOME_ID), set as front page."
fi

wpcli post create --post_type=page --post_title="About Masterworks Academy" --post_status=publish --post_name="about" --post_content="Masterworks Academy is the research and thought leadership platform of Masterworks — the only platform purpose-built to help investors understand art market performance and returns." --porcelain 2>/dev/null || true
echo "  Created About page."

# ----- Create sample content -----
echo ""
echo "[6/9] Creating sample content..."

# --- Research Reports ---
echo "  Creating Research Reports..."

wpcli post create \
    --post_type=research-report \
    --post_title="Masterworks Art Market Index — Q1 2026" \
    --post_status=publish \
    --post_name="art-market-index-q1-2026" \
    --post_excerpt="Blue-chip art returned 8.3% in Q1 2026, outperforming the S&P 500 by 240 basis points. Contemporary art led all segments." \
    --post_content='<h2>Executive Summary</h2>
<p>The Masterworks Art Market Index rose 8.3% in Q1 2026, driven by exceptional strength in Contemporary and Post-War segments. This marks the fourth consecutive quarter of positive returns for blue-chip art, with the asset class continuing to demonstrate low correlation to public equities.</p>

<h2>Key Findings</h2>
<p>Contemporary art led all segments with a 12.1% return, powered by strong demand from Asian collectors and record-breaking results at Christie&apos;s Hong Kong evening sale. Post-War art followed at 9.4%, while Ultra-Contemporary cooled to 3.2% after its 2025 surge.</p>

<h3>Market Dynamics</h3>
<p>Total auction turnover reached $4.2 billion in Q1, up 18% year-over-year. The sell-through rate at major auctions held steady at 82%, indicating healthy demand across price tiers. Private sales continued to gain share, estimated at 30% of total market volume.</p>

<h3>Macro Context</h3>
<p>With the Fed holding rates at 4.25% and inflation moderating to 2.4%, the macro backdrop remains constructive for alternative assets. Real yields have compressed, supporting allocations to non-yielding stores of value including art, gold, and collectibles.</p>

<h2>Segment Performance</h2>
<ul>
<li><strong>Contemporary:</strong> +12.1% — Basquiat, Richter, and Kusama drove gains</li>
<li><strong>Post-War:</strong> +9.4% — Warhol market rebounded strongly</li>
<li><strong>Impressionist:</strong> +6.8% — Steady institutional demand</li>
<li><strong>Ultra-Contemporary:</strong> +3.2% — Normalization after 2025 overheating</li>
<li><strong>Photography:</strong> +4.5% — Growing collector base</li>
</ul>

<h2>Outlook</h2>
<p>We expect continued strength through the spring auction season, with May sales at Christie&apos;s and Sotheby&apos;s projected to test $6 billion in combined turnover. Key risk: a reacceleration of inflation could pressure discretionary spending at the top end.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=research-report \
    --post_title="Monthly Market Update — March 2026" \
    --post_status=publish \
    --post_name="market-update-march-2026" \
    --post_excerpt="March brought record results at Phillips' London contemporary sale and a notable Basquiat private transaction above $50M." \
    --post_content='<h2>March 2026 Market Recap</h2>
<p>The art market saw robust activity in March, with Phillips&apos; London contemporary evening sale setting a house record at £142 million. Demand was particularly strong for works under £1 million, suggesting broadening participation beyond ultra-high-net-worth collectors.</p>

<h3>Auction Highlights</h3>
<ul>
<li><strong>Phillips London:</strong> £142M total (est. £95-130M), 91% sell-through</li>
<li><strong>Bonhams Post-War:</strong> Strong results for secondary market names</li>
<li><strong>Heritage Auctions:</strong> Record online-only contemporary sale ($28M)</li>
</ul>

<h3>Notable Private Sales</h3>
<p>A Jean-Michel Basquiat skull painting transacted privately for approximately $52 million, according to sources familiar with the deal. This represents a 34% increase from the work&apos;s last auction appearance in 2019, translating to a ~4.3% annualized return over 7 years.</p>

<h3>What to Watch in April</h3>
<p>All eyes are on the Hong Kong spring sales, where Christie&apos;s and Sotheby&apos;s will test Asian collector appetite. The Frieze Seoul satellite events may provide early signals for Ultra-Contemporary pricing trends.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=research-report \
    --post_title="Q4 2025 Portfolio Performance Update" \
    --post_status=publish \
    --post_name="portfolio-update-q4-2025" \
    --post_excerpt="Masterworks portfolio returned 11.2% in Q4 2025. Three offerings exited with average net annualized returns of 14.7%." \
    --post_content='<h2>Q4 2025 Performance Summary</h2>
<p>The Masterworks portfolio delivered an aggregate 11.2% return in Q4 2025, driven by three successful exits and strong mark-to-market appreciation across the remaining holdings. This brings the trailing twelve-month portfolio return to 17.8%.</p>

<h3>Exits</h3>
<ul>
<li><strong>Banksy, "Love is in the Bin" Study</strong> — Sold at Christie&apos;s for $3.8M, net annualized return: 18.2% over 2.4 years</li>
<li><strong>George Condo, "Staring Into Space"</strong> — Private sale at $2.1M, net annualized return: 12.1% over 3.1 years</li>
<li><strong>Yayoi Kusama, Infinity Net (Red)</strong> — Sold at Sotheby&apos;s for $5.4M, net annualized return: 13.8% over 1.9 years</li>
</ul>

<h3>Current Holdings</h3>
<p>The remaining portfolio of 47 works has a combined appraised value of $284 million, up from $261 million at the end of Q3. The largest position remains a Basquiat untitled work acquired in 2023, currently valued at $18.5 million (up 22% from cost).</p>' \
    --porcelain 2>/dev/null || true

# --- Artist Dossiers ---
echo "  Creating Artist Dossiers..."

wpcli post create \
    --post_type=artist-dossier \
    --post_title="Jean-Michel Basquiat: Market Intelligence Report" \
    --post_status=publish \
    --post_name="basquiat-market-report" \
    --post_excerpt="Basquiat remains the highest-performing artist in the Masterworks universe with a 14.2% average annualized return across all tracked auction sales since 2000." \
    --post_content='<h2>Artist Overview</h2>
<p>Jean-Michel Basquiat (1960-1988) remains the defining blue-chip Contemporary artist of the post-2010 market era. His market has proven remarkably resilient across economic cycles, with strong collector demand from both Western and Asian buyers underpinning consistent price appreciation.</p>

<h2>Market Position</h2>
<p>Basquiat&apos;s total auction market in 2025 reached $487 million across 142 lots, with a sell-through rate of 89%. His work has generated an average annualized return of 14.2% since 2000, outperforming every major equity index over the same period.</p>

<h3>Price Tiers</h3>
<ul>
<li><strong>Museum-quality (>$10M):</strong> 8 works sold in 2025, avg. premium to estimate: 22%</li>
<li><strong>Mid-market ($1-10M):</strong> 34 works sold, avg. premium: 15%</li>
<li><strong>Entry-level (<$1M):</strong> 100 works sold (works on paper, prints), avg. premium: 8%</li>
</ul>

<h2>Key Metrics</h2>
<ul>
<li>Total auction lots tracked: 2,847</li>
<li>Average annualized return: 14.2%</li>
<li>Price range: $50,000 — $110.5M</li>
<li>Market trajectory: <strong>Rising</strong></li>
<li>Repeat sale index (base 100, year 2000): 892</li>
</ul>

<h2>Notable Recent Sales</h2>
<ul>
<li>"Untitled (Devil)" — $52M private sale, March 2026</li>
<li>"Flexible" — $41.8M at Christie&apos;s NY, November 2025</li>
<li>"King Pleasure" — $28.3M at Sotheby&apos;s London, October 2025</li>
</ul>

<h2>Exhibition Momentum</h2>
<p>The Basquiat exhibition pipeline remains strong, with a major retrospective planned at the Centre Pompidou (Paris, Fall 2026) and a collaborative show with Andy Warhol at the Fondation Louis Vuitton (Spring 2027). Exhibition activity is a leading indicator of market strength — historically, major retrospectives precede 12-18 month periods of accelerating prices.</p>

<h2>Investment Thesis</h2>
<p>Basquiat offers the rare combination of blue-chip reliability and continued price appreciation. His market is deep enough to absorb significant capital without price distortion, yet supply-constrained enough (finite estate, no new works) to support structural price growth. We rate Basquiat as a core holding for any art-focused portfolio.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=artist-dossier \
    --post_title="Yayoi Kusama: Market Intelligence Report" \
    --post_status=publish \
    --post_name="kusama-market-report" \
    --post_excerpt="Kusama's market has grown 340% over the past decade, driven by global museum demand and a broadening collector base across Asia, Europe, and the Americas." \
    --post_content='<h2>Artist Overview</h2>
<p>Yayoi Kusama (b. 1929) is the world&apos;s most popular living artist by museum attendance and one of the most commercially successful artists of any era. Her Infinity Rooms, polka dot motifs, and pumpkin sculptures have achieved crossover cultural status, driving both institutional and retail collector demand.</p>

<h2>Market Position</h2>
<p>Kusama&apos;s total auction market reached $312 million in 2025, a record year driven by strong sales across all price tiers. Her Infinity Net paintings, the most investment-grade segment of her output, have delivered an average annualized return of 11.8% since 2010.</p>

<h2>Key Metrics</h2>
<ul>
<li>Total auction lots tracked: 4,221</li>
<li>Average annualized return (Infinity Nets): 11.8%</li>
<li>Price range: $10,000 — $18.4M</li>
<li>Market trajectory: <strong>Rising</strong></li>
</ul>

<h2>Investment Thesis</h2>
<p>Kusama benefits from an unusually broad collector base — her work appeals to Contemporary art specialists, Asian collectors, female-focused collection mandates, and cultural institutions alike. This diversified demand base provides downside protection that few artists can match. The primary risk is her age (96) and the potential for a post-mortem market correction, though historical data suggests blue-chip artist markets typically strengthen in the 2-5 years following an artist&apos;s passing.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=artist-dossier \
    --post_title="Banksy: Market Intelligence Report" \
    --post_status=publish \
    --post_name="banksy-market-report" \
    --post_excerpt="Banksy is the highest-volume Contemporary artist at auction with 1,800+ lots annually. His market has matured from street art novelty to institutional-grade alternative asset." \
    --post_content='<h2>Artist Overview</h2>
<p>Banksy (identity unconfirmed, active since 1990s) has transformed from a guerrilla street artist into one of the most commercially significant artists of the 21st century. His market uniquely bridges pop culture and fine art, attracting a collector base that skews younger and more geographically diverse than traditional blue-chip artists.</p>

<h2>Market Position</h2>
<p>With over 1,800 lots sold at auction in 2025, Banksy has the highest transaction volume of any Contemporary artist. Total auction turnover reached $198 million, with strong demand across editions, unique works, and authenticated street pieces. The average annualized return for unique Banksy works has been 9.7% since 2010.</p>

<h2>Key Metrics</h2>
<ul>
<li>Total auction lots tracked: 8,934</li>
<li>Average annualized return (unique works): 9.7%</li>
<li>Price range: $5,000 — $25.4M</li>
<li>Market trajectory: <strong>Stable</strong></li>
</ul>

<h2>Investment Thesis</h2>
<p>Banksy offers high liquidity relative to other artists — his works sell quickly and with low transaction friction. The identity question creates both upside (a reveal would likely be a catalyst event) and risk (market disruption if controversial). We view Banksy as a tactical allocation for portfolios seeking liquidity and cultural relevance.</p>' \
    --porcelain 2>/dev/null || true

# --- Market Commentary ---
echo "  Creating Market Commentary..."

wpcli post create \
    --post_type=market-commentary \
    --post_title="Why the Fed's Rate Hold Is Bullish for Art" \
    --post_status=publish \
    --post_name="fed-rate-hold-bullish-art" \
    --post_excerpt="With rates steady at 4.25% and inflation cooling, the macro setup for art as an alternative store of value hasn't been this favorable since 2019." \
    --post_content='<h2>The Macro Setup</h2>
<p>The Federal Reserve held rates at 4.25% for the third consecutive meeting, signaling patience as inflation continues its gradual descent toward the 2% target. For art market participants, this is unambiguously positive news.</p>

<h2>Why It Matters for Art</h2>
<p>Art prices have historically shown negative correlation to real interest rates. When real yields compress — as they are now — capital flows toward non-yielding stores of value. Gold, real estate, and art all benefit from this dynamic.</p>

<p>The key insight: art&apos;s correlation to rates is <em>asymmetric</em>. Rate cuts help art prices more than rate hikes hurt them, because the collector base for blue-chip art is wealthy enough to absorb higher financing costs. What drives art demand is wealth creation and wealth preservation motivation — and both are elevated right now.</p>

<h2>Historical Pattern</h2>
<p>In the last three rate-pause periods (2006-07, 2018-19, 2023-24), the Masterworks Art Market Index gained an average of 9.2% over the subsequent 12 months. We are now 8 months into the current pause.</p>

<h2>Our View</h2>
<p>We rate the current macro environment as <strong>Bullish</strong> for art. The combination of stable rates, cooling inflation, and robust wealth creation (S&P 500 up 14% YTD) creates favorable conditions for the upcoming spring auction season. Collectors tend to buy when they feel wealthy but uncertain about traditional assets — and that describes the current moment perfectly.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=market-commentary \
    --post_title="KAWS: Oversaturated or Undervalued?" \
    --post_status=publish \
    --post_name="kaws-oversaturated-or-undervalued" \
    --post_excerpt="KAWS prices have declined 22% from their 2021 peak. Is this a buying opportunity or a structural reset? The data tells a nuanced story." \
    --post_content='<h2>The Bear Case</h2>
<p>KAWS (Brian Donnelly, b. 1974) has seen his auction market cool significantly from the frenzied highs of 2021. Average prices for unique paintings are down 22% from peak, and his edition market — once the gateway drug for new collectors — has softened by nearly 40%.</p>

<p>Critics point to oversupply: KAWS has been prolific, and the sheer volume of available work creates pricing pressure. His collaboration with brands (Uniqlo, Dior, McDonald&apos;s) may have diluted the exclusivity that drives art market premiums.</p>

<h2>The Bull Case</h2>
<p>However, the bearish narrative misses important nuance. KAWS&apos;s <em>museum-quality unique works</em> — large-scale paintings and sculptures — have actually held value, declining only 8% from peak. The weakness is concentrated in editions and smaller works, which were arguably overpriced during the pandemic-era frenzy.</p>

<p>Institutional interest remains strong: KAWS has shows scheduled at three major museums in 2026-27, and his collector base continues to diversify geographically, with growing demand from Southeast Asian and Middle Eastern buyers.</p>

<h2>Our Rating: Hold</h2>
<p>We see KAWS as a <strong>Hold</strong> at current levels. The edition market needs time to absorb excess supply, but the unique work market is fairly valued. For new positions, we&apos;d wait for prices to stabilize for another 2-3 quarters before adding.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=market-commentary \
    --post_title="The Case for Ultra-Contemporary Art in 2026" \
    --post_status=publish \
    --post_name="ultra-contemporary-art-2026" \
    --post_excerpt="After a volatile 2024-25, Ultra-Contemporary art is entering a more rational phase. Here's who we're watching and why." \
    --post_content='<h2>Defining Ultra-Contemporary</h2>
<p>Ultra-Contemporary refers to artists born after 1974 whose primary market is still active. This segment is the highest-risk, highest-reward corner of the art market — and after a turbulent two years, it&apos;s worth reassessing.</p>

<h2>The Reset</h2>
<p>After the speculative surge of 2021-23, Ultra-Contemporary prices corrected 15-30% across most artists. This was healthy. The segment had attracted too much speculative capital, particularly from crypto-wealth buyers who have since pulled back.</p>

<p>What remains is a smaller but more serious collector base, more rational pricing, and several artists whose institutional credentials have continued to strengthen even as prices moderated.</p>

<h2>Artists to Watch</h2>
<ul>
<li><strong>Flora Yukhnovich</strong> — Prices stabilized after a 30% correction. Museum acquisitions in 2025 suggest institutional validation catching up to market pricing.</li>
<li><strong>Jadé Fadojutimi</strong> — Strong exhibition pipeline, growing secondary market with healthy sell-through rates above 85%.</li>
<li><strong>Lucy Bull</strong> — Early career but significant institutional momentum. Risk is high but asymmetric — downside is limited at current low price points.</li>
</ul>

<h2>Our View</h2>
<p>Ultra-Contemporary art is a <strong>selective Buy</strong> in 2026 for investors with high risk tolerance and a 5+ year horizon. Focus on artists with strong gallery representation, institutional exhibition history, and limited edition output.</p>' \
    --porcelain 2>/dev/null || true

# --- Explainers ---
echo "  Creating Explainers..."

wpcli post create \
    --post_type=explainer \
    --post_title="How Art Investing Works: A Complete Guide" \
    --post_status=publish \
    --post_name="how-art-investing-works" \
    --post_excerpt="Everything you need to know about investing in fine art — from how returns are generated to how Masterworks makes it accessible." \
    --post_content='<h2>Art as an Asset Class</h2>
<p>Fine art has been a store of wealth for centuries, but it has only recently become accessible as an investable asset class for individual investors. Historically, art investing was limited to ultra-high-net-worth collectors who could afford to purchase entire works — often costing millions of dollars.</p>

<p>Today, platforms like Masterworks allow investors to purchase fractional shares of blue-chip artworks, democratizing access to an asset class that has delivered an average of 12.6% annualized returns over the past 25 years (per the Masterworks Art Market Index).</p>

<h2>How Returns Are Generated</h2>
<p>Art generates returns through price appreciation — the difference between what a work is acquired for and what it sells for. Unlike stocks or bonds, art does not produce cash flows (dividends or interest). Instead, returns come from:</p>

<ul>
<li><strong>Supply scarcity:</strong> Great artists produce a finite body of work. As works enter permanent museum collections, the available supply shrinks over time.</li>
<li><strong>Demand growth:</strong> Global wealth creation, museum expansion, and growing cultural interest in art expand the collector base.</li>
<li><strong>Inflation hedging:</strong> Art is a real asset whose value tends to keep pace with or exceed inflation over time.</li>
<li><strong>Portfolio diversification:</strong> Art has low correlation (0.1-0.2) to public equities, providing genuine diversification benefits.</li>
</ul>

<h2>How Masterworks Works</h2>
<ol>
<li><strong>Acquisition:</strong> Masterworks&apos; research team identifies works by artists with strong market fundamentals — consistent demand, price appreciation, and institutional support.</li>
<li><strong>SEC Filing:</strong> Each work is filed with the SEC as a separate offering, creating shares that investors can purchase.</li>
<li><strong>Holding Period:</strong> Works are typically held for 3-7 years while the market appreciates.</li>
<li><strong>Sale:</strong> When market conditions are favorable, Masterworks sells the work — at auction, via private sale, or to an institution.</li>
<li><strong>Distribution:</strong> Proceeds are distributed to shareholders proportionally.</li>
</ol>

<h2>Frequently Asked Questions</h2>

<h3>What is the minimum investment?</h3>
<p>Masterworks allows investments starting at $1,000 per offering, making blue-chip art accessible to a broad range of investors.</p>

<h3>How long do I hold my investment?</h3>
<p>The typical holding period is 3-7 years. Investors can also sell their shares on the Masterworks secondary market before the artwork is sold.</p>

<h3>What are the fees?</h3>
<p>Masterworks charges a 1.5% annual management fee and a 20% profit share on gains at the time of sale. There are no upfront purchase fees.</p>

<h3>Is art a risky investment?</h3>
<p>Like all investments, art carries risk. However, blue-chip art has shown lower volatility than public equities and has not experienced a sustained downturn of more than 10% in the past 25 years. Diversification across multiple artists and segments helps mitigate risk.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=explainer \
    --post_title="Understanding IRR in Art Investing" \
    --post_status=publish \
    --post_name="understanding-irr-art-investing" \
    --post_excerpt="Internal Rate of Return (IRR) is the standard metric for evaluating art investment performance. Here's how to interpret it and why it matters." \
    --post_content='<h2>What Is IRR?</h2>
<p>Internal Rate of Return (IRR) is the annualized rate of return that makes the net present value of all cash flows equal to zero. In plain English: it&apos;s the annual percentage your investment grew, accounting for the time your money was invested.</p>

<h2>Why IRR Matters for Art</h2>
<p>Art investments don&apos;t produce regular income like dividends or rent. You invest a lump sum, wait, and receive proceeds when the work sells. IRR captures the <em>time value</em> of that investment — a 50% total return over 2 years (21.6% IRR) is very different from a 50% return over 10 years (4.1% IRR).</p>

<h2>How to Compare</h2>
<ul>
<li><strong>S&P 500 average IRR:</strong> ~10% per year</li>
<li><strong>Real estate (REITs):</strong> ~8-11% per year</li>
<li><strong>Blue-chip art (Masterworks Index):</strong> ~12-14% per year</li>
<li><strong>Private equity:</strong> ~15-18% per year (but less liquid)</li>
</ul>

<p>Art&apos;s IRR is competitive with other alternative assets, with the added benefit of low correlation to public markets.</p>' \
    --porcelain 2>/dev/null || true

# --- Daily News ---
echo "  Creating Daily News posts..."

wpcli post create \
    --post_type=daily-news \
    --post_title="The Daily Brushstroke — April 2, 2026" \
    --post_status=publish \
    --post_name="daily-brushstroke-april-2-2026" \
    --post_excerpt="Christie's Hong Kong preview opens strong, Fed minutes signal patience, and a rare Modigliani hits the market." \
    --post_content='<h2>Art Market News</h2>

<h3>Christie&apos;s Hong Kong Preview Opens to Strong Collector Interest</h3>
<p>The spring preview for Christie&apos;s Hong Kong evening sale opened yesterday to what insiders described as "the most engaged preview crowd in three years." The highlight lot — a major Zao Wou-Ki triptych estimated at HK$180-250 million — reportedly has multiple interested parties. The sale takes place April 8.</p>
<p><strong>Why it matters:</strong> Hong Kong spring sales are the leading indicator for Asian collector appetite, which has been the primary growth engine for the global art market since 2019.</p>

<h3>Rare Modigliani Portrait Surfaces at Sotheby&apos;s</h3>
<p>Sotheby&apos;s announced a rare Amedeo Modigliani portrait for its May New York evening sale, estimated at $60-80 million. The work has been in the same private collection since 1972 and has never appeared at auction.</p>
<p><strong>Why it matters:</strong> Fresh-to-market works by canonical artists generate the strongest prices. This Modigliani&apos;s provenance and rarity make it a potential record-setter.</p>

<h2>Financial & Economic News</h2>

<h3>Fed Minutes: "Patient Approach" to Further Rate Adjustments</h3>
<p>Minutes from the March FOMC meeting revealed broad consensus for maintaining rates at 4.25%, with members citing "encouraging but incomplete" progress on inflation. Markets now price zero rate cuts before September.</p>
<p><strong>Why it matters:</strong> Rate stability supports the "store of value" narrative for art. A longer pause means more time for wealth creation without the headwind of rising rates.</p>

<h3>Global Wealth Report: UHNW Population Grew 7.2% in 2025</h3>
<p>Knight Frank&apos;s Wealth Report found that the ultra-high-net-worth population ($30M+ net worth) grew 7.2% globally in 2025, reaching 626,000 individuals. Asia-Pacific led growth at 9.8%.</p>
<p><strong>Why it matters:</strong> More UHNW individuals = more potential art buyers. The art market&apos;s total addressable market is directly proportional to the UHNW population.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=daily-news \
    --post_title="The Daily Brushstroke — April 1, 2026" \
    --post_status=publish \
    --post_name="daily-brushstroke-april-1-2026" \
    --post_excerpt="Phillips expands in Seoul, Masterworks exits a Cecily Brown, and Treasury yields slide on soft jobs data." \
    --post_content='<h2>Art Market News</h2>

<h3>Phillips Opens Permanent Seoul Gallery</h3>
<p>Phillips announced the opening of its first permanent gallery space in Seoul&apos;s Gangnam district, signaling continued confidence in the Korean collecting market. The 8,000 sq ft space will host exhibitions and private sales.</p>
<p><strong>Why it matters:</strong> Seoul has emerged as Asia&apos;s fastest-growing art hub. Phillips joining Christie&apos;s and Sotheby&apos;s with a permanent presence validates the market&apos;s maturity.</p>

<h3>Masterworks Exits Cecily Brown Painting at 16.4% Net IRR</h3>
<p>Masterworks announced the sale of Cecily Brown&apos;s "Figures in a Landscape" (2018) for $4.2 million, representing a 16.4% net annualized return for investors over a 2.8-year holding period. The work was sold via private sale to a European collector.</p>
<p><strong>Why it matters:</strong> Another strong exit from the Masterworks portfolio, and further validation of the Cecily Brown market&apos;s institutional strength.</p>

<h2>Financial & Economic News</h2>

<h3>Treasury Yields Dip on Softer-Than-Expected Jobs Data</h3>
<p>The 10-year Treasury yield fell 8 basis points to 4.12% after March payrolls came in below expectations (142K vs. 185K consensus). The unemployment rate ticked up to 4.1%.</p>
<p><strong>Why it matters:</strong> Lower yields increase the relative attractiveness of non-yielding assets like art. If the labor market continues to soften, it strengthens the case for eventual rate cuts — a catalyst for art prices.</p>' \
    --porcelain 2>/dev/null || true

# --- White Paper ---
echo "  Creating White Papers..."

wpcli post create \
    --post_type=white-paper \
    --post_title="Art in the Portfolio: An Allocation Framework for Financial Advisors" \
    --post_status=publish \
    --post_name="art-allocation-framework-advisors" \
    --post_excerpt="A research-backed framework for incorporating art into client portfolios, including optimal allocation ranges, risk analysis, and implementation considerations." \
    --post_content='<h2>Abstract</h2>
<p>This white paper presents a quantitative framework for incorporating fine art into diversified investment portfolios. Using 25 years of auction data comprising 50 million+ transactions, we demonstrate that a 5-15% allocation to blue-chip art can improve portfolio Sharpe ratios by 0.15-0.30 while reducing maximum drawdown by 200-400 basis points.</p>

<h2>Key Findings</h2>
<ul>
<li>Art&apos;s correlation to the S&P 500 is 0.12 (2000-2025), providing genuine diversification</li>
<li>Art&apos;s correlation to bonds is -0.08, making it complementary in balanced portfolios</li>
<li>Optimal allocation ranges from 5% (conservative) to 15% (aggressive) depending on client risk profile and liquidity needs</li>
<li>Blue-chip art has a maximum drawdown of -12% vs. -34% for the S&P 500 (2000-2025)</li>
</ul>

<h2>Portfolio Construction</h2>
<p>We recommend a core-satellite approach: a core allocation of 3-5% to blue-chip artists with established secondary markets (Basquiat, Warhol, Kusama, Richter), supplemented by a satellite allocation of 2-5% to higher-growth segments (Ultra-Contemporary, emerging markets). This structure balances stability with upside potential.</p>

<h2>Implementation</h2>
<p>For financial advisors, the practical challenge has historically been access. Masterworks&apos; SEC-qualified offerings provide a regulated, fractional ownership structure that fits within standard brokerage workflows and can be reported on consolidated statements.</p>

<h2>Risk Considerations</h2>
<ul>
<li><strong>Liquidity:</strong> Art is less liquid than public equities. Holding periods of 3-7 years are typical.</li>
<li><strong>Valuation:</strong> Art does not have continuous pricing. Valuations are based on comparable sales and expert appraisal.</li>
<li><strong>Concentration:</strong> Individual artist risk can be significant. Diversification across 10+ artists is recommended.</li>
</ul>' \
    --porcelain 2>/dev/null || true

# --- Cultural Updates ---
echo "  Creating Cultural Updates..."

wpcli post create \
    --post_type=cultural-update \
    --post_title="Inside Christie's Record-Breaking Hong Kong Season" \
    --post_status=publish \
    --post_name="christies-hong-kong-spring-2026" \
    --post_excerpt="A preview of Christie's Hong Kong spring sales, featuring a landmark Zao Wou-Ki triptych and the strongest consignment lineup in three years." \
    --post_content='<h2>The Lineup</h2>
<p>Christie&apos;s Hong Kong spring evening sale on April 8 features the strongest consignment lineup since the pandemic era, headlined by a monumental Zao Wou-Ki triptych estimated at HK$180-250 million ($23-32M USD). The 150-lot sale carries a total low estimate of HK$1.8 billion ($230M USD).</p>

<h2>Key Lots to Watch</h2>
<ul>
<li><strong>Zao Wou-Ki, Triptych (1987-88):</strong> The largest Zao work to appear at auction in five years. Market watchers will be watching whether it breaks the artist&apos;s $65M record.</li>
<li><strong>Yoshitomo Nara, "Sleepless Night":</strong> A major canvas from 2001, estimated HK$50-70M. Nara&apos;s market has been cooling — this result will signal whether the floor has been found.</li>
<li><strong>Gerhard Richter, "Abstraktes Bild":</strong> A vibrant squeegee painting from 1994, estimated HK$80-120M. Richter remains one of the most consistently performing blue-chip artists.</li>
</ul>

<h2>What We&apos;re Watching</h2>
<p>The sell-through rate will be more telling than individual results. A rate above 85% would signal healthy demand; below 75% would suggest Asian collector caution. We&apos;ll publish a full recap and analysis within 24 hours of the sale.</p>' \
    --porcelain 2>/dev/null || true

wpcli post create \
    --post_type=cultural-update \
    --post_title="Masterworks Announces Three New Offerings for Q2 2026" \
    --post_status=publish \
    --post_name="new-offerings-q2-2026" \
    --post_excerpt="Three new investment opportunities now open: a Keith Haring, a Julie Mehretu, and a rare early Basquiat collaboration." \
    --post_content='<h2>New Offerings</h2>
<p>Masterworks is pleased to announce three new SEC-qualified offerings for Q2 2026, expanding our portfolio across blue-chip and high-growth Contemporary segments.</p>

<h3>1. Keith Haring, "Untitled" (1984)</h3>
<p>A large-scale acrylic on canvas from Haring&apos;s most sought-after period. Haring&apos;s market has shown consistent 10%+ annual appreciation, with particular strength in works from 1982-1986. This work was acquired from a European private collection.</p>

<h3>2. Julie Mehretu, "Stadia II" Study (2004)</h3>
<p>A dynamic mixed-media work on paper by one of the most important abstract painters working today. Mehretu&apos;s market has grown 280% over the past decade, accelerating after her 2019 Whitney retrospective and 2021 Met Breuer commission.</p>

<h3>3. Jean-Michel Basquiat x Andy Warhol, Collaboration (1984)</h3>
<p>An exceptionally rare collaborative work from the legendary Basquiat-Warhol partnership. Only approximately 160 collaborative works were created, and fewer than 20 have appeared at auction in the past decade. This category of work has appreciated at 18%+ annually.</p>

<h2>How to Invest</h2>
<p>All three offerings are now live on the Masterworks platform. Minimum investment is $1,000 per offering. Visit masterworks.com to view full details, condition reports, and provenance documentation.</p>' \
    --porcelain 2>/dev/null || true

# --- Regular blog posts for variety ---
echo "  Creating standard blog posts..."

wpcli post create \
    --post_type=post \
    --post_title="Welcome to Masterworks Academy" \
    --post_status=publish \
    --post_name="welcome-to-masterworks-academy" \
    --post_excerpt="Introducing Masterworks Academy — your research hub for art market intelligence, data, and investment insights." \
    --post_content='<h2>Why We Built This</h2>
<p>The art market suffers from an information asymmetry problem. Most investors don&apos;t understand how art markets work, how returns are generated, or how to evaluate performance. Meanwhile, existing research sources serve art professionals — not investors.</p>

<p>Masterworks Academy fills that gap. Powered by our proprietary database of 50 million+ auction records, we publish rigorous, investor-focused research that helps you make informed decisions about art as an asset class.</p>

<h2>What You&apos;ll Find Here</h2>
<ul>
<li><strong>Research Reports:</strong> Quarterly indices, market updates, and portfolio performance data</li>
<li><strong>Artist Intelligence:</strong> Deep-dive profiles on the artists that move markets</li>
<li><strong>Market Commentary:</strong> Timely analysis connecting macro trends to art prices</li>
<li><strong>Daily News:</strong> The Daily Brushstroke — your morning briefing on art and finance</li>
<li><strong>Education:</strong> Explainers and guides for investors new to art</li>
<li><strong>Data & Indices:</strong> Open, citable data on art market performance</li>
</ul>

<p>Whether you&apos;re an existing Masterworks investor, a financial advisor, or someone curious about art as an investment — this is your hub.</p>' \
    --porcelain 2>/dev/null || true

# ----- Assign taxonomy terms to posts -----
echo ""
echo "[7/9] Assigning taxonomy terms to posts..."

# Get post IDs and assign terms
# Research Reports -> Research pillar
for post_id in $(wpcli post list --post_type=research-report --format=ids); do
    wpcli post term set "$post_id" content-pillar "Research" 2>/dev/null || true
    wpcli post term set "$post_id" audience "Existing Investors" "Financial Advisors" 2>/dev/null || true
done

# Artist Dossiers -> Research pillar
for post_id in $(wpcli post list --post_type=artist-dossier --format=ids); do
    wpcli post term set "$post_id" content-pillar "Research" 2>/dev/null || true
    wpcli post term set "$post_id" art-segment "Contemporary" 2>/dev/null || true
done

# Market Commentary -> Opinions pillar
for post_id in $(wpcli post list --post_type=market-commentary --format=ids); do
    wpcli post term set "$post_id" content-pillar "Opinions & Explainers" 2>/dev/null || true
    wpcli post term set "$post_id" audience "Existing Investors" "Prospective Investors" 2>/dev/null || true
done

# Explainers -> Opinions pillar
for post_id in $(wpcli post list --post_type=explainer --format=ids); do
    wpcli post term set "$post_id" content-pillar "Opinions & Explainers" 2>/dev/null || true
    wpcli post term set "$post_id" audience "Prospective Investors" 2>/dev/null || true
done

# Daily News -> Daily News pillar
for post_id in $(wpcli post list --post_type=daily-news --format=ids); do
    wpcli post term set "$post_id" content-pillar "Daily News" 2>/dev/null || true
    wpcli post term set "$post_id" audience "Existing Investors" "Prospective Investors" "Media & Analysts" 2>/dev/null || true
done

# White Papers -> Research pillar
for post_id in $(wpcli post list --post_type=white-paper --format=ids); do
    wpcli post term set "$post_id" content-pillar "Research" 2>/dev/null || true
    wpcli post term set "$post_id" audience "Financial Advisors" 2>/dev/null || true
done

# Cultural Updates -> Cultural Updates pillar
for post_id in $(wpcli post list --post_type=cultural-update --format=ids); do
    wpcli post term set "$post_id" content-pillar "Cultural Updates" 2>/dev/null || true
done

echo "  Done."

# ----- Create navigation menu -----
echo ""
echo "[8/9] Creating navigation menu..."

MENU_EXISTS=$(wpcli menu list --format=ids 2>/dev/null | head -1)
if [ -z "$MENU_EXISTS" ]; then
    MENU_ID=$(wpcli menu create "Academy Main" --porcelain 2>/dev/null || echo "")
    if [ -n "$MENU_ID" ]; then
        wpcli menu item add-custom "$MENU_ID" "Home" "http://localhost:8080/" 2>/dev/null || true
        wpcli menu item add-custom "$MENU_ID" "Research" "http://localhost:8080/research-report/" 2>/dev/null || true
        wpcli menu item add-custom "$MENU_ID" "Artists" "http://localhost:8080/artist-dossier/" 2>/dev/null || true
        wpcli menu item add-custom "$MENU_ID" "Data" "http://localhost:8080/data-index/" 2>/dev/null || true
        wpcli menu item add-custom "$MENU_ID" "Daily News" "http://localhost:8080/daily-news/" 2>/dev/null || true
        wpcli menu item add-custom "$MENU_ID" "Culture" "http://localhost:8080/cultural-update/" 2>/dev/null || true
        wpcli menu location assign "$MENU_ID" academy-main 2>/dev/null || true
        echo "  Menu created and assigned."
    fi
else
    echo "  Menu already exists, skipping."
fi

# ----- Final status -----
echo ""
echo "[9/9] Final status..."
echo ""
echo "  Posts created:"
wpcli post list --post_type=research-report --format=count 2>/dev/null | xargs -I{} echo "    Research Reports: {}"
wpcli post list --post_type=artist-dossier --format=count 2>/dev/null | xargs -I{} echo "    Artist Dossiers:  {}"
wpcli post list --post_type=market-commentary --format=count 2>/dev/null | xargs -I{} echo "    Commentary:       {}"
wpcli post list --post_type=explainer --format=count 2>/dev/null | xargs -I{} echo "    Explainers:       {}"
wpcli post list --post_type=daily-news --format=count 2>/dev/null | xargs -I{} echo "    Daily News:       {}"
wpcli post list --post_type=white-paper --format=count 2>/dev/null | xargs -I{} echo "    White Papers:     {}"
wpcli post list --post_type=cultural-update --format=count 2>/dev/null | xargs -I{} echo "    Cultural Updates: {}"
wpcli post list --post_type=post --format=count 2>/dev/null | xargs -I{} echo "    Blog Posts:       {}"
echo ""
echo "  Active plugins:"
wpcli plugin list --status=active --format=table 2>/dev/null || true
echo ""
echo "  Active theme:"
wpcli theme list --status=active --format=table 2>/dev/null || true

echo ""
echo "=================================="
echo "  Setup complete!"
echo "=================================="
echo ""
echo "  Open: http://localhost:8080"
echo "  Admin: http://localhost:8080/wp-admin/"
echo ""
