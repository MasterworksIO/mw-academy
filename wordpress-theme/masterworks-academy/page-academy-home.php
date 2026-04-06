<?php
/**
 * Template Name: Academy Home
 *
 * Alternative page template for the Academy homepage.
 * Can be assigned to the "Academy Home" page in case front-page.php
 * does not apply due to subfolder routing.
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

get_header();

// Get featured post.
$featured_post_id = get_theme_mod( 'mw_featured_post', 0 );
$featured_query   = null;

if ( $featured_post_id ) {
    $featured_query = new WP_Query( array(
        'p'              => absint( $featured_post_id ),
        'post_type'      => 'post',
        'posts_per_page' => 1,
    ) );
}

// Fallback to latest sticky or most recent post.
if ( ! $featured_query || ! $featured_query->have_posts() ) {
    $sticky = get_option( 'sticky_posts' );
    if ( ! empty( $sticky ) ) {
        $featured_query = new WP_Query( array(
            'post__in'       => $sticky,
            'posts_per_page' => 1,
            'post_type'      => 'post',
        ) );
    } else {
        $featured_query = new WP_Query( array(
            'posts_per_page' => 1,
            'post_type'      => 'post',
        ) );
    }
}
?>

<!-- Hero Section -->
<?php if ( $featured_query && $featured_query->have_posts() ) : ?>
    <section class="hero">
        <div class="container">
            <?php
            while ( $featured_query->have_posts() ) :
                $featured_query->the_post();
                ?>
                <article class="card card--hero">
                    <div class="card__image">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'mw-hero', array( 'loading' => 'eager' ) ); ?>
                        <?php else : ?>
                            <div style="width:100%;height:100%;background:var(--mw-bg-alt);display:flex;align-items:center;justify-content:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="var(--mw-border)" stroke-width="1" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card__content">
                        <?php mw_category_badge(); ?>
                        <h2 class="card__title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <p class="card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                        <div class="card__meta">
                            <div class="card__author">
                                <?php echo get_avatar( get_the_author_meta( 'ID' ), 28, '', '', array( 'class' => 'card__author-avatar' ) ); ?>
                                <span class="card__author-name"><?php the_author(); ?></span>
                            </div>
                            <span class="card__date"><?php mw_posted_on(); ?></span>
                            <span class="card__reading-time"><?php mw_reading_time(); ?></span>
                        </div>
                    </div>
                </article>
                <?php
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </section>
<?php endif; ?>

<!-- Filter Bar -->
<section class="filter-section">
    <div class="container">
        <div class="filter-bar" role="tablist" aria-label="<?php esc_attr_e( 'Filter articles by category', 'masterworks-academy' ); ?>">
            <button class="filter-bar__item is-active" data-category="all" role="tab" aria-selected="true">
                <?php esc_html_e( 'All', 'masterworks-academy' ); ?>
            </button>
            <button class="filter-bar__item" data-category="research" role="tab" aria-selected="false">
                <?php esc_html_e( 'Research', 'masterworks-academy' ); ?>
            </button>
            <button class="filter-bar__item" data-category="data-indices" role="tab" aria-selected="false">
                <?php esc_html_e( 'Data', 'masterworks-academy' ); ?>
            </button>
            <button class="filter-bar__item" data-category="opinions" role="tab" aria-selected="false">
                <?php esc_html_e( 'Opinions', 'masterworks-academy' ); ?>
            </button>
            <button class="filter-bar__item" data-category="daily-news" role="tab" aria-selected="false">
                <?php esc_html_e( 'News', 'masterworks-academy' ); ?>
            </button>
            <button class="filter-bar__item" data-category="culture" role="tab" aria-selected="false">
                <?php esc_html_e( 'Culture', 'masterworks-academy' ); ?>
            </button>
        </div>
    </div>
</section>

<!-- Content Grid + Sidebar -->
<section class="section">
    <div class="container">
        <div class="grid grid--sidebar">

            <!-- Articles Grid -->
            <div>
                <div class="grid grid--3" id="articles-grid">
                    <?php
                    $main_query = new WP_Query( array(
                        'post_type'      => 'post',
                        'posts_per_page' => 12,
                        'post_status'    => 'publish',
                        'post__not_in'   => ( $featured_post_id ) ? array( $featured_post_id ) : array(),
                    ) );

                    if ( $main_query->have_posts() ) :
                        while ( $main_query->have_posts() ) :
                            $main_query->the_post();
                            get_template_part( 'template-parts/content', 'card' );
                        endwhile;
                        wp_reset_postdata();
                    else :
                        ?>
                        <p class="no-results text-secondary">
                            <?php esc_html_e( 'No articles found. Check back soon for new content.', 'masterworks-academy' ); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Load More -->
                <?php if ( $main_query->max_num_pages > 1 ) : ?>
                    <div class="load-more" id="load-more-container" data-max-pages="<?php echo esc_attr( $main_query->max_num_pages ); ?>" data-current-page="1">
                        <button class="btn btn--ghost btn--lg" id="load-more-btn">
                            <span class="btn__text"><?php esc_html_e( 'Load More Articles', 'masterworks-academy' ); ?></span>
                            <span class="spinner" aria-hidden="true"></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar" role="complementary">
                <?php if ( is_active_sidebar( 'academy-sidebar' ) ) : ?>
                    <?php dynamic_sidebar( 'academy-sidebar' ); ?>
                <?php endif; ?>

                <!-- Most Read -->
                <div class="sidebar__section">
                    <h3 class="sidebar__title"><?php esc_html_e( 'Most Read', 'masterworks-academy' ); ?></h3>
                    <div class="sidebar__list">
                        <?php
                        $popular_query = new WP_Query( array(
                            'post_type'      => 'post',
                            'posts_per_page' => 5,
                            'post_status'    => 'publish',
                            'orderby'        => 'comment_count',
                            'order'          => 'DESC',
                        ) );

                        $counter = 1;
                        if ( $popular_query->have_posts() ) :
                            while ( $popular_query->have_posts() ) :
                                $popular_query->the_post();
                                ?>
                                <div class="sidebar__list-item">
                                    <span class="sidebar__list-number"><?php echo esc_html( $counter ); ?></span>
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </div>
                                <?php
                                $counter++;
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                </div>

                <!-- Sidebar Newsletter -->
                <div class="sidebar__section">
                    <div class="newsletter newsletter--compact">
                        <h3 class="newsletter__title"><?php esc_html_e( 'The Daily Brushstroke', 'masterworks-academy' ); ?></h3>
                        <p class="newsletter__description">
                            <?php esc_html_e( 'Art market intelligence, delivered daily.', 'masterworks-academy' ); ?>
                        </p>
                        <form class="newsletter__form" action="#" method="post">
                            <?php wp_nonce_field( 'mw_newsletter', 'mw_newsletter_nonce_sidebar' ); ?>
                            <input type="email" name="email" class="newsletter__input"
                                   placeholder="<?php esc_attr_e( 'Your email', 'masterworks-academy' ); ?>"
                                   required
                                   aria-label="<?php esc_attr_e( 'Email address', 'masterworks-academy' ); ?>">
                            <button type="submit" class="newsletter__submit">
                                <?php esc_html_e( 'Subscribe', 'masterworks-academy' ); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</section>

<?php
get_footer();
