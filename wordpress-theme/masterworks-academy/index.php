<?php
/**
 * Main Index Template (Fallback)
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

get_header();
?>

<div class="container section">
    <?php if ( is_search() ) : ?>
        <header class="page-header">
            <h1 class="page-header__title">
                <?php
                /* translators: %s: search query */
                printf( esc_html__( 'Search results for: %s', 'masterworks-academy' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
                ?>
            </h1>
        </header>
    <?php elseif ( is_archive() ) : ?>
        <header class="page-header">
            <?php
            the_archive_title( '<h1 class="page-header__title">', '</h1>' );
            the_archive_description( '<p class="page-header__description text-secondary">', '</p>' );
            ?>
        </header>
    <?php endif; ?>

    <?php if ( have_posts() ) : ?>
        <div class="grid grid--3 mt-xl">
            <?php
            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content', 'card' );
            endwhile;
            ?>
        </div>

        <div class="load-more mt-2xl">
            <?php
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => esc_html__( '&larr; Previous', 'masterworks-academy' ),
                'next_text' => esc_html__( 'Next &rarr;', 'masterworks-academy' ),
            ) );
            ?>
        </div>
    <?php else : ?>
        <div class="no-results" style="text-align: center; padding: var(--space-4xl) 0;">
            <h2><?php esc_html_e( 'Nothing found', 'masterworks-academy' ); ?></h2>
            <p class="text-secondary mt-md">
                <?php esc_html_e( 'It seems we can\'t find what you\'re looking for. Try searching for something else.', 'masterworks-academy' ); ?>
            </p>
            <div class="mt-xl">
                <?php get_search_form(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
