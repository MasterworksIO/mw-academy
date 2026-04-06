<?php
/**
 * Template Part: Content List Item
 *
 * Horizontal article list item — thumbnail left, content right.
 * Used on the front page article list.
 *
 * @package Masterworks_Academy
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<article <?php post_class( 'article-list-item' ); ?>>

    <!-- Thumbnail -->
    <div class="article-list-item__image">
        <?php if ( has_post_thumbnail() ) : ?>
            <a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                <?php
                the_post_thumbnail( 'mw-card-thumb', array(
                    'loading' => 'lazy',
                    'alt'     => esc_attr( get_the_title() ),
                ) );
                ?>
            </a>
        <?php else : ?>
            <a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                <div class="article-list-item__image-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="var(--mw-border)" stroke-width="1" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                </div>
            </a>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="article-list-item__content">

        <!-- Meta line: avatar + author + date + reading time -->
        <div class="article-list-item__meta">
            <?php echo get_avatar( get_the_author_meta( 'ID' ), 22, '', esc_attr( get_the_author() ), array( 'class' => 'author-avatar', 'loading' => 'lazy' ) ); ?>
            <span class="author-name"><?php the_author(); ?></span>
            <span class="meta-separator" aria-hidden="true"></span>
            <span class="meta-date"><?php mw_posted_on( 'F jS, Y' ); ?></span>
            <span class="meta-separator" aria-hidden="true"></span>
            <span class="meta-reading-time"><?php mw_reading_time(); ?></span>
        </div>

        <!-- Title -->
        <h3 class="article-list-item__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <!-- Excerpt -->
        <p class="article-list-item__excerpt">
            <?php echo esc_html( get_the_excerpt() ); ?>
        </p>

        <!-- Read More -->
        <a href="<?php the_permalink(); ?>" class="article-list-item__read-more">
            <?php esc_html_e( 'Read Article', 'masterworks-academy' ); ?>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </a>

    </div>

</article>
