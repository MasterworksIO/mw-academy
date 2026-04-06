<?php
/**
 * Front Page — institutional research platform layout
 *
 * @package Masterworks_Academy
 * @since 3.0.0
 */

get_header();

$all_types = array( 'post', 'research-report', 'artist-dossier', 'market-commentary', 'explainer', 'data-index', 'daily-news', 'white-paper', 'cultural-update' );

// Hero: sticky or latest
$sticky = get_option( 'sticky_posts' );
$hero_args = array( 'post_type' => $all_types, 'posts_per_page' => 1, 'post_status' => 'publish' );
if ( ! empty( $sticky ) ) { $hero_args['post__in'] = $sticky; }
$hero_query = new WP_Query( $hero_args );
$hero_id = 0;
?>

<!-- Webinar Banner -->
<div class="ins-ticker">
    <div class="ins-ticker__inner">
        <span class="ins-ticker__badge">Live Webinar</span>
        <span class="ins-ticker__text">Art Market Outlook: Q2 2026 — with Scott Lynn &amp; the Masterworks Research Team</span>
        <span class="ins-ticker__date">April 17, 2026 &middot; 1:00 PM ET</span>
        <a href="https://masterworks.com/events" class="ins-ticker__cta">RSVP &rarr;</a>
    </div>
</div>

<!-- Featured Article — white bg -->
<?php if ( $hero_query->have_posts() ) : while ( $hero_query->have_posts() ) : $hero_query->the_post(); $hero_id = get_the_ID(); ?>
<section class="ins-featured">
    <div class="container">
        <div class="ins-featured__grid">
            <div class="ins-featured__content">
                <div class="ins-label"><?php echo esc_html( strtoupper( get_post_type_object( get_post_type() )->labels->singular_name ) ); ?></div>
                <h1 class="ins-featured__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
                <p class="ins-featured__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                <div class="ins-featured__meta">
                    <?php echo get_avatar( get_the_author_meta( 'ID' ), 32, '', '', array( 'class' => 'ins-featured__avatar' ) ); ?>
                    <span><?php the_author(); ?></span>
                    <span class="ins-sep">&middot;</span>
                    <span><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></span>
                </div>
            </div>
            <div class="ins-featured__image">
                <?php if ( has_post_thumbnail() ) : ?>
                    <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large' ); ?></a>
                <?php else : ?>
                    <a href="<?php the_permalink(); ?>" class="ins-placeholder">MASTERWORKS</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endwhile; wp_reset_postdata(); endif; ?>

<!-- Latest Insights — off-white bg -->
<?php
$latest_query = new WP_Query( array(
    'post_type' => $all_types, 'posts_per_page' => 3, 'post_status' => 'publish',
    'post__not_in' => $hero_id ? array( $hero_id ) : array(),
) );
?>
<?php if ( $latest_query->have_posts() ) : ?>
<section class="ins-section ins-section--gray">
    <div class="container">
        <div class="ins-section__header">
            <h2>Latest Insights</h2>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">View All &rarr;</a>
        </div>
        <div class="ins-grid-3">
            <?php while ( $latest_query->have_posts() ) : $latest_query->the_post(); ?>
            <?php get_template_part( 'template-parts/content', 'ins-card' ); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Price Database CTA — dark -->
<section class="ins-db-cta">
    <div class="container">
        <div class="ins-db-cta__grid">
            <div class="ins-db-cta__content">
                <h2 class="ins-db-cta__title">Masterworks Price Database</h2>
                <p class="ins-db-cta__desc">The most comprehensive art market dataset available to investors. Search 50M+ auction records across 15,000+ artists to understand appreciation rates by artist market.</p>
                <div class="ins-db-cta__stats">
                    <div class="ins-db-cta__stat">
                        <span class="ins-db-cta__stat-num">50M+</span>
                        <span class="ins-db-cta__stat-label">Auction records</span>
                    </div>
                    <div class="ins-db-cta__stat">
                        <span class="ins-db-cta__stat-num">15,000+</span>
                        <span class="ins-db-cta__stat-label">Artists tracked</span>
                    </div>
                    <div class="ins-db-cta__stat">
                        <span class="ins-db-cta__stat-num">25+ yrs</span>
                        <span class="ins-db-cta__stat-label">Historical data</span>
                    </div>
                </div>
                <a href="https://masterworks.com/price-database" class="ins-db-cta__button">Explore the Price Database &rarr;</a>
            </div>
            <div class="ins-db-cta__chart">
                <!-- Tufte-style: multi-line comparison chart with end-labels -->
                <div class="ins-db-cta__chart-header">
                    <span class="ins-db-cta__chart-title">Masterworks Art Market Indices</span>
                    <span class="ins-db-cta__chart-period">10-year cumulative return, indexed to 100</span>
                </div>
                <div class="ins-db-cta__chart-main">
                    <svg viewBox="0 0 480 200" preserveAspectRatio="xMidYMid meet" class="ins-db-cta__tufte">
                        <defs>
                            <linearGradient id="tg1" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#3838E6" stop-opacity="0.08"/><stop offset="100%" stop-color="#3838E6" stop-opacity="0"/></linearGradient>
                        </defs>

                        <!-- Subtle horizontal gridlines — Tufte: light, no axis box -->
                        <line x1="40" y1="20" x2="400" y2="20" stroke="rgba(255,255,255,0.06)" stroke-width="0.5"/>
                        <line x1="40" y1="60" x2="400" y2="60" stroke="rgba(255,255,255,0.06)" stroke-width="0.5"/>
                        <line x1="40" y1="100" x2="400" y2="100" stroke="rgba(255,255,255,0.06)" stroke-width="0.5"/>
                        <line x1="40" y1="140" x2="400" y2="140" stroke="rgba(255,255,255,0.06)" stroke-width="0.5"/>
                        <line x1="40" y1="180" x2="400" y2="180" stroke="rgba(255,255,255,0.06)" stroke-width="0.5"/>

                        <!-- Y-axis labels — minimal -->
                        <text x="36" y="23" fill="rgba(255,255,255,0.3)" font-size="8" text-anchor="end" font-family="sans-serif">260</text>
                        <text x="36" y="63" fill="rgba(255,255,255,0.3)" font-size="8" text-anchor="end" font-family="sans-serif">220</text>
                        <text x="36" y="103" fill="rgba(255,255,255,0.3)" font-size="8" text-anchor="end" font-family="sans-serif">180</text>
                        <text x="36" y="143" fill="rgba(255,255,255,0.3)" font-size="8" text-anchor="end" font-family="sans-serif">140</text>
                        <text x="36" y="183" fill="rgba(255,255,255,0.3)" font-size="8" text-anchor="end" font-family="sans-serif">100</text>

                        <!-- X-axis year labels -->
                        <text x="40" y="196" fill="rgba(255,255,255,0.3)" font-size="8" font-family="sans-serif">2016</text>
                        <text x="112" y="196" fill="rgba(255,255,255,0.3)" font-size="8" font-family="sans-serif">2018</text>
                        <text x="184" y="196" fill="rgba(255,255,255,0.3)" font-size="8" font-family="sans-serif">2020</text>
                        <text x="256" y="196" fill="rgba(255,255,255,0.3)" font-size="8" font-family="sans-serif">2022</text>
                        <text x="328" y="196" fill="rgba(255,255,255,0.3)" font-size="8" font-family="sans-serif">2024</text>
                        <text x="388" y="196" fill="rgba(255,255,255,0.3)" font-size="8" font-family="sans-serif">2026</text>

                        <!-- Area fill under Contemporary line -->
                        <polygon fill="url(#tg1)" points="40,180 58,176 76,170 94,168 112,160 130,148 148,155 166,138 184,120 202,128 220,115 238,105 256,118 274,108 292,95 310,88 328,78 346,72 364,60 382,48 400,32 400,180"/>

                        <!-- S&P 500 benchmark — thin gray dashed -->
                        <polyline fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1" stroke-dasharray="3,3"
                            points="40,180 58,177 76,172 94,168 112,162 130,155 148,165 166,158 184,140 202,135 220,138 238,130 256,142 274,135 292,128 310,122 328,115 346,108 364,100 382,95 400,88"/>

                        <!-- Post-War — teal -->
                        <polyline fill="none" stroke="#34DFDF" stroke-width="1.5"
                            points="40,180 58,178 76,174 94,170 112,165 130,158 148,162 166,150 184,138 202,142 220,132 238,125 256,130 274,120 292,112 310,105 328,96 346,88 364,78 382,68 400,55"/>

                        <!-- Contemporary — brand purple, thicker (hero line) -->
                        <polyline fill="none" stroke="#3838E6" stroke-width="2.5"
                            points="40,180 58,176 76,170 94,168 112,160 130,148 148,155 166,138 184,120 202,128 220,115 238,105 256,118 274,108 292,95 310,88 328,78 346,72 364,60 382,48 400,32"/>

                        <!-- Impressionist — gold, thin -->
                        <polyline fill="none" stroke="#FCDD62" stroke-width="1.5" stroke-opacity="0.7"
                            points="40,180 58,179 76,176 94,175 112,172 130,168 148,170 166,165 184,158 202,160 220,155 238,150 256,153 274,148 292,142 310,138 328,132 346,128 364,122 382,118 400,108"/>

                        <!-- End-labels (Tufte: label at the data, not in a legend) -->
                        <text x="404" y="34" fill="#3838E6" font-size="8" font-weight="600" font-family="sans-serif">Contemporary +152%</text>
                        <text x="404" y="57" fill="#34DFDF" font-size="8" font-weight="500" font-family="sans-serif">Post-War +128%</text>
                        <text x="404" y="90" fill="rgba(255,255,255,0.35)" font-size="8" font-family="sans-serif">S&amp;P 500 +94%</text>
                        <text x="404" y="110" fill="#FCDD62" font-size="8" font-weight="500" font-family="sans-serif" opacity="0.8">Impressionist +74%</text>

                        <!-- End dots -->
                        <circle cx="400" cy="32" r="3" fill="#3838E6"/>
                        <circle cx="400" cy="55" r="2.5" fill="#34DFDF"/>
                        <circle cx="400" cy="88" r="2" fill="rgba(255,255,255,0.3)"/>
                        <circle cx="400" cy="108" r="2.5" fill="#FCDD62" opacity="0.8"/>
                    </svg>
                </div>
                <div class="ins-db-cta__chart-footer">
                    <span>Source: Masterworks Art Market Index, public auction data. Past performance does not guarantee future results.</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Artist Intelligence — white bg -->
<?php
$artists_query = new WP_Query( array(
    'post_type' => 'artist-dossier', 'posts_per_page' => 3, 'post_status' => 'publish',
) );
?>
<?php if ( $artists_query->have_posts() ) : ?>
<section class="ins-section">
    <div class="container">
        <div class="ins-section__header">
            <h2>Artist Intelligence</h2>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'artist-dossier' ) ); ?>">View All &rarr;</a>
        </div>
        <div class="ins-grid-3">
            <?php while ( $artists_query->have_posts() ) : $artists_query->the_post(); ?>
            <?php get_template_part( 'template-parts/content', 'ins-card' ); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- White Paper Lead Magnet -->
<section class="ins-leadmagnet">
    <div class="container">
        <div class="ins-leadmagnet__grid">
            <div class="ins-leadmagnet__preview">
                <div class="ins-leadmagnet__cover">
                    <div class="ins-leadmagnet__cover-inner">
                        <div class="ins-leadmagnet__cover-logo">MASTERWORKS</div>
                        <div class="ins-leadmagnet__cover-title">2026 Art Market Outlook</div>
                        <div class="ins-leadmagnet__cover-subtitle">Research &amp; Analysis</div>
                        <div class="ins-leadmagnet__cover-year">Q2 2026</div>
                    </div>
                </div>
            </div>
            <div class="ins-leadmagnet__content">
                <div class="ins-label">Free Report</div>
                <h2 class="ins-leadmagnet__title">Our 2026 Art Market Outlook</h2>
                <p class="ins-leadmagnet__desc">A comprehensive look at the forces shaping art market performance in 2026 — from macro conditions and auction trends to emerging collector demographics and the artists we're watching most closely.</p>
                <ul class="ins-leadmagnet__list">
                    <li>Blue-chip art segment performance and 12-month forecasts</li>
                    <li>Correlation analysis: art vs. equities, bonds, and alternatives</li>
                    <li>Top 10 artists by risk-adjusted return potential</li>
                    <li>Geographic demand shifts and institutional buying trends</li>
                </ul>
                <form class="ins-leadmagnet__form" action="#" method="post">
                    <input type="email" name="email" placeholder="Enter your email to download" required>
                    <button type="submit">Download the Report</button>
                </form>
                <div class="ins-leadmagnet__fine">Free. No spam. Unsubscribe anytime.</div>
            </div>
        </div>
    </div>
</section>

<!-- Research & Commentary — off-white bg -->
<?php
$exclude_ids = $hero_id ? array( $hero_id ) : array();
$research_query = new WP_Query( array(
    'post_type' => array( 'research-report', 'market-commentary', 'white-paper', 'cultural-update' ),
    'posts_per_page' => 3, 'post_status' => 'publish',
    'post__not_in' => $exclude_ids,
) );
?>
<?php if ( $research_query->have_posts() ) : ?>
<section class="ins-section ins-section--gray">
    <div class="container">
        <div class="ins-section__header">
            <h2>Research & Commentary</h2>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'market-commentary' ) ); ?>">View All &rarr;</a>
        </div>
        <div class="ins-grid-3">
            <?php while ( $research_query->have_posts() ) : $research_query->the_post(); ?>
            <?php get_template_part( 'template-parts/content', 'ins-card' ); ?>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Video — off-white bg -->
<section class="ins-section ins-section--gray">
    <div class="container">
        <div class="ins-section__header">
            <h2>Featured Video</h2>
        </div>
        <div class="ins-video ins-video--reversed">
            <div class="ins-video__info">
                <div class="ins-label">Live Event Recap</div>
                <h3 class="ins-video__title">Live Analysis: Sotheby's $400M+ Sale</h3>
                <p class="ins-video__desc">The Masterworks team breaks down the results from Sotheby's blockbuster evening sale — including record-setting lots, surprise underbids, and what the results signal about collector sentiment heading into the spring auction season.</p>
                <p class="ins-video__desc">Key topics covered:</p>
                <ul class="ins-video__list">
                    <li>Which artists outperformed estimates and why</li>
                    <li>Lot-by-lot analysis of the top 10 results</li>
                    <li>Asian collector demand and its impact on pricing</li>
                    <li>What the sell-through rate tells us about market health</li>
                    <li>Implications for Masterworks portfolio holdings</li>
                </ul>
                <a href="https://masterworks.com/how-it-works" class="ins-video__link">Learn more about investing with Masterworks &rarr;</a>
            </div>
            <div class="ins-video__embed">
                <iframe src="https://www.youtube.com/embed/esg4xS26TIs?modestbranding=1&rel=0&showinfo=0&controls=1&iv_load_policy=3&color=white" title="Masterworks — Sotheby's $400M+ Sale Analysis" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- Learn the Basics — white bg -->
<?php
$basics_query = new WP_Query( array(
    'post_type' => array( 'explainer', 'post' ), 'posts_per_page' => 4, 'post_status' => 'publish',
) );
?>
<?php if ( $basics_query->have_posts() ) : ?>
<section class="ins-section">
    <div class="container">
        <div class="ins-section__header">
            <h2>Learn the Basics</h2>
        </div>
        <div class="ins-list">
            <?php while ( $basics_query->have_posts() ) : $basics_query->the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="ins-list__item">
                <div>
                    <div class="ins-label"><?php echo esc_html( strtoupper( get_post_type_object( get_post_type() )->labels->singular_name ) ); ?></div>
                    <div class="ins-list__title"><?php the_title(); ?></div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
