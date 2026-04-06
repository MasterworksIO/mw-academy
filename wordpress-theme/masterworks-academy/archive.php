<?php
/**
 * Archive Template — grid with filters
 *
 * @package Masterworks_Academy
 * @since 2.3.0
 */

get_header();

// Clean archive title
$archive_title = wp_strip_all_tags( get_the_archive_title() );
$archive_title = preg_replace( '/^(Archives|Category|Tag|Author|Post Type):\s*/i', '', $archive_title );
$archive_desc  = get_the_archive_description();

// Get current post type for context
$current_type = get_queried_object();
$post_type_slug = '';
if ( is_post_type_archive() ) {
    $post_type_slug = get_query_var( 'post_type' );
}

// Get relevant taxonomies for filters
$segments = get_terms( array( 'taxonomy' => 'art-segment', 'hide_empty' => true ) );
$pillars  = get_terms( array( 'taxonomy' => 'content-pillar', 'hide_empty' => true ) );
$artists  = get_terms( array( 'taxonomy' => 'artist-name', 'hide_empty' => true ) );
?>

<!-- Archive Header -->
<section class="archive-header">
    <div class="container">
        <h1 class="archive-header__title"><?php echo esc_html( $archive_title ); ?></h1>
        <?php if ( $archive_desc ) : ?>
            <p class="archive-header__desc"><?php echo wp_kses_post( wp_strip_all_tags( $archive_desc ) ); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Filters -->
<section class="archive-filters">
    <div class="container">
        <div class="archive-filters__bar">

            <!-- Sort -->
            <div class="archive-filters__group">
                <label class="archive-filters__label">Sort by</label>
                <select class="archive-filters__select" id="archive-sort" onchange="window.location.search='?orderby='+this.value">
                    <option value="date" <?php selected( isset($_GET['orderby']) && $_GET['orderby'] === 'date' ); ?>>Most Recent</option>
                    <option value="title" <?php selected( isset($_GET['orderby']) && $_GET['orderby'] === 'title' ); ?>>Title A-Z</option>
                </select>
            </div>

            <?php if ( ! empty( $pillars ) && ! is_wp_error( $pillars ) ) : ?>
            <div class="archive-filters__group">
                <label class="archive-filters__label">Category</label>
                <select class="archive-filters__select" id="archive-pillar">
                    <option value="">All</option>
                    <?php foreach ( $pillars as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $segments ) && ! is_wp_error( $segments ) && $post_type_slug !== 'explainer' ) : ?>
            <div class="archive-filters__group">
                <label class="archive-filters__label">Segment</label>
                <select class="archive-filters__select" id="archive-segment">
                    <option value="">All Segments</option>
                    <?php foreach ( $segments as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $artists ) && ! is_wp_error( $artists ) && in_array( $post_type_slug, array( 'artist-dossier', 'market-commentary', '' ), true ) ) : ?>
            <div class="archive-filters__group">
                <label class="archive-filters__label">Artist</label>
                <select class="archive-filters__select" id="archive-artist">
                    <option value="">All Artists</option>
                    <?php foreach ( $artists as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="archive-filters__count">
                <?php
                global $wp_query;
                printf( '%d %s', $wp_query->found_posts, _n( 'article', 'articles', $wp_query->found_posts, 'masterworks-academy' ) );
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Archive Grid -->
<section class="archive-content">
    <div class="container">
        <?php if ( have_posts() ) : ?>
        <div class="archive-grid">
            <?php while ( have_posts() ) : the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="archive-card">
                <div class="archive-card__image">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium_large' ); ?>
                    <?php else : ?>
                        <div class="archive-card__placeholder">MASTERWORKS</div>
                    <?php endif; ?>
                </div>
                <div class="archive-card__body">
                    <div class="archive-card__label">
                        <?php echo esc_html( strtoupper( get_post_type_object( get_post_type() )->labels->singular_name ) ); ?>
                    </div>
                    <h2 class="archive-card__title"><?php the_title(); ?></h2>
                    <p class="archive-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
                    <div class="archive-card__meta"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>

        <div class="archive-pagination">
            <?php
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '&larr; Previous',
                'next_text' => 'Next &rarr;',
            ) );
            ?>
        </div>

        <?php else : ?>
        <p class="archive-empty">No articles found.</p>
        <?php endif; ?>
    </div>
</section>

<?php get_footer(); ?>
