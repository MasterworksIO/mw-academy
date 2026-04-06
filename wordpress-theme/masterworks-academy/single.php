<?php
/**
 * Single Post Template — editorial layout
 * Full-width image above, title below, narrow body column
 *
 * @package Masterworks_Academy
 * @since 2.3.0
 */

get_header();

$all_types = array( 'post', 'research-report', 'artist-dossier', 'market-commentary', 'explainer', 'data-index', 'daily-news', 'white-paper', 'cultural-update' );
?>

<?php while ( have_posts() ) : the_post(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'article-editorial' ); ?>>

    <!-- Full-width Image -->
    <?php if ( has_post_thumbnail() ) : ?>
    <div class="article-hero-img">
        <?php the_post_thumbnail( 'full' ); ?>
    </div>
    <?php endif; ?>

    <!-- Article Header -->
    <header class="article-header">
        <div class="article-header__label">
            <?php echo esc_html( strtoupper( get_post_type_object( get_post_type() )->labels->singular_name ) ); ?>
        </div>
        <h1 class="article-header__title"><?php the_title(); ?></h1>
        <?php if ( has_excerpt() ) : ?>
            <p class="article-header__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
        <?php endif; ?>

        <!-- Author + Share -->
        <div class="article-header__meta">
            <div class="article-header__author">
                <?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', esc_attr( get_the_author() ), array( 'class' => 'article-header__avatar' ) ); ?>
                <div>
                    <div class="article-header__name"><?php the_author(); ?></div>
                    <div class="article-header__date"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></div>
                </div>
            </div>
            <div class="article-header__share">
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>&text=<?php echo urlencode( get_the_title() ); ?>" target="_blank" rel="noopener" aria-label="Share on X">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
                <a href="mailto:?subject=<?php echo urlencode( get_the_title() ); ?>&body=<?php echo urlencode( get_permalink() ); ?>" aria-label="Share via email">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Article Body -->
    <div class="article-body">
        <div class="article-body__content entry-content">
            <?php the_content(); ?>
        </div>
    </div>

    <!-- Back link -->
    <div class="article-back">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; Back to Academy</a>
    </div>

</article>

<!-- Recommended Articles -->
<?php
$related_query = new WP_Query( array(
    'post_type'      => $all_types,
    'posts_per_page' => 3,
    'post_status'    => 'publish',
    'post__not_in'   => array( get_the_ID() ),
    'orderby'        => 'rand',
) );
?>
<?php if ( $related_query->have_posts() ) : ?>
<section class="recommended">
    <div class="recommended__inner">
        <h2 class="recommended__title">Recommended</h2>
        <div class="recommended__grid">
            <?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="recommended__card">
                <div class="recommended__card-image">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium_large' ); ?>
                    <?php else : ?>
                        <div class="academy-card__image-placeholder">MASTERWORKS</div>
                    <?php endif; ?>
                </div>
                <div class="recommended__card-label">
                    <?php echo esc_html( strtoupper( get_post_type_object( get_post_type() )->labels->singular_name ) ); ?>
                </div>
                <h3 class="recommended__card-title"><?php the_title(); ?></h3>
                <div class="recommended__card-date"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></div>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php endwhile; ?>

<?php get_footer(); ?>
