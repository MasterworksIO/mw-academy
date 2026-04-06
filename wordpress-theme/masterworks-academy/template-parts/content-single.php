<?php
/**
 * Template Part: Single Article Content
 *
 * Full article layout for single post view.
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-article' ); ?>>

    <!-- Article Header -->
    <header class="article-header">
        <div class="container container--narrow">
            <?php mw_category_badge(); ?>

            <h1 class="article-header__title"><?php the_title(); ?></h1>

            <!-- Meta Bar -->
            <div class="article-meta">
                <div class="article-meta__author">
                    <?php echo get_avatar( get_the_author_meta( 'ID' ), 36, '', esc_attr( get_the_author() ), array( 'loading' => 'lazy' ) ); ?>
                    <div>
                        <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" class="article-meta__author-name">
                            <?php the_author(); ?>
                        </a>
                    </div>
                </div>

                <span class="article-meta__divider" aria-hidden="true"></span>

                <span class="article-meta__date">
                    <?php mw_posted_on( 'F j, Y' ); ?>
                </span>

                <span class="article-meta__divider" aria-hidden="true"></span>

                <span class="article-meta__reading-time">
                    <?php mw_reading_time(); ?>
                </span>

                <span class="article-meta__divider" aria-hidden="true"></span>

                <?php mw_share_buttons(); ?>
            </div>
        </div>
    </header>

    <!-- Featured Image -->
    <?php if ( has_post_thumbnail() ) : ?>
        <div class="article-featured-image">
            <div class="container">
                <?php
                the_post_thumbnail( 'mw-featured', array(
                    'loading' => 'eager',
                    'alt'     => esc_attr( get_the_title() ),
                ) );
                ?>
                <?php if ( get_the_post_thumbnail_caption() ) : ?>
                    <p class="article-featured-image__caption text-sm text-light" style="text-align:center;margin-top:var(--space-sm);">
                        <?php echo esc_html( get_the_post_thumbnail_caption() ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Article Body -->
    <div class="article-body container container--narrow">
        <?php the_content(); ?>
    </div>

    <!-- Tags -->
    <?php
    $tags = get_the_tags();
    if ( ! empty( $tags ) ) :
        ?>
        <div class="container container--narrow">
            <div class="article-tags">
                <span class="article-tags__label"><?php esc_html_e( 'Topics:', 'masterworks-academy' ); ?></span>
                <?php
                foreach ( $tags as $tag ) :
                    printf(
                        '<a href="%s" rel="tag">%s</a>',
                        esc_url( get_tag_link( $tag->term_id ) ),
                        esc_html( $tag->name )
                    );
                endforeach;
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Author Bio Box -->
    <div class="container container--narrow">
        <div class="author-bio">
            <?php echo get_avatar( get_the_author_meta( 'ID' ), 80, '', esc_attr( get_the_author() ), array( 'class' => 'author-bio__avatar', 'loading' => 'lazy' ) ); ?>
            <div class="author-bio__content">
                <h4 class="author-bio__name">
                    <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
                        <?php the_author(); ?>
                    </a>
                </h4>
                <?php
                $author_title = get_the_author_meta( 'job_title' );
                if ( $author_title ) :
                    ?>
                    <p class="author-bio__title"><?php echo esc_html( $author_title ); ?></p>
                <?php endif; ?>

                <?php if ( get_the_author_meta( 'description' ) ) : ?>
                    <p class="author-bio__description"><?php echo esc_html( get_the_author_meta( 'description' ) ); ?></p>
                <?php endif; ?>

                <div class="author-bio__links">
                    <?php
                    $author_twitter = get_the_author_meta( 'twitter' );
                    if ( $author_twitter ) :
                        ?>
                        <a href="<?php echo esc_url( $author_twitter ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Author on X', 'masterworks-academy' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php
                    $author_linkedin = get_the_author_meta( 'linkedin' );
                    if ( $author_linkedin ) :
                        ?>
                        <a href="<?php echo esc_url( $author_linkedin ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Author on LinkedIn', 'masterworks-academy' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" aria-label="<?php esc_attr_e( 'View all posts', 'masterworks-academy' ); ?>">
                        <?php esc_html_e( 'All Posts', 'masterworks-academy' ); ?> &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Articles -->
    <?php
    $categories = get_the_category();
    $cat_ids    = array();
    if ( ! empty( $categories ) ) {
        foreach ( $categories as $cat ) {
            $cat_ids[] = $cat->term_id;
        }
    }

    $related_query = new WP_Query( array(
        'post_type'      => 'post',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
        'post__not_in'   => array( get_the_ID() ),
        'category__in'   => $cat_ids,
        'orderby'        => 'rand',
    ) );

    if ( $related_query->have_posts() ) :
        ?>
        <section class="related-articles">
            <div class="container">
                <h2 class="related-articles__title"><?php esc_html_e( 'Related Articles', 'masterworks-academy' ); ?></h2>
                <div class="grid grid--3">
                    <?php
                    while ( $related_query->have_posts() ) :
                        $related_query->the_post();
                        get_template_part( 'template-parts/content', 'card' );
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Newsletter CTA -->
    <section class="section">
        <div class="container container--narrow">
            <div class="newsletter">
                <div class="newsletter__badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6V7.5z" />
                    </svg>
                    <?php esc_html_e( 'Stay Informed', 'masterworks-academy' ); ?>
                </div>
                <h2 class="newsletter__title"><?php esc_html_e( 'The Daily Brushstroke', 'masterworks-academy' ); ?></h2>
                <p class="newsletter__description">
                    <?php esc_html_e( 'Get art market intelligence delivered to your inbox every morning. Trusted by 50,000+ collectors and investors.', 'masterworks-academy' ); ?>
                </p>
                <form class="newsletter__form" action="#" method="post">
                    <?php wp_nonce_field( 'mw_newsletter', 'mw_newsletter_nonce_article' ); ?>
                    <input
                        type="email"
                        name="email"
                        class="newsletter__input"
                        placeholder="<?php esc_attr_e( 'Enter your email', 'masterworks-academy' ); ?>"
                        required
                        aria-label="<?php esc_attr_e( 'Email address', 'masterworks-academy' ); ?>"
                    >
                    <button type="submit" class="newsletter__submit">
                        <?php esc_html_e( 'Subscribe', 'masterworks-academy' ); ?>
                    </button>
                </form>
                <p class="newsletter__privacy">
                    <?php esc_html_e( 'No spam. Unsubscribe anytime.', 'masterworks-academy' ); ?>
                </p>
            </div>
        </div>
    </section>

</article>
