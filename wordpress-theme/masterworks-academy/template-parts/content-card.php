<?php
/**
 * Template Part: Content Card
 *
 * Renders as either a featured overlay card or a standard grid card
 * depending on the 'card_style' query var.
 *
 * Usage:
 *   set_query_var( 'card_style', 'featured' );
 *   get_template_part( 'template-parts/content', 'card' );
 *
 * @package Masterworks_Academy
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$card_style = get_query_var( 'card_style', 'standard' );
$is_featured = ( 'featured' === $card_style );
?>

<?php if ( $is_featured ) : ?>

    <!-- Featured Card (Image Overlay) -->
    <article <?php post_class( 'card--featured' ); ?>>
        <div class="card__image">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php
                the_post_thumbnail( 'mw-card-thumb-lg', array(
                    'loading' => 'eager',
                    'alt'     => esc_attr( get_the_title() ),
                ) );
                ?>
            <?php else : ?>
                <div class="card__image-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,0.3)" stroke-width="1" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                </div>
            <?php endif; ?>
            <div class="card__image-overlay"></div>
        </div>

        <div class="card__content">
            <?php mw_category_badge(); ?>

            <h3 class="card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>

            <div class="card__meta">
                <div class="card__author">
                    <?php echo get_avatar( get_the_author_meta( 'ID' ), 24, '', esc_attr( get_the_author() ), array( 'class' => 'card__author-avatar', 'loading' => 'lazy' ) ); ?>
                    <span class="card__author-name"><?php the_author(); ?></span>
                </div>
            </div>
        </div>
    </article>

<?php else : ?>

    <!-- Standard Card (Grid) -->
    <article <?php post_class( 'card' ); ?>>

        <!-- Featured Image -->
        <div class="card__image">
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
                    <div style="width:100%;height:100%;background:var(--mw-bg-alt);display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="var(--mw-border)" stroke-width="1" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                        </svg>
                    </div>
                </a>
            <?php endif; ?>

            <!-- Category Badge -->
            <?php mw_category_badge(); ?>
        </div>

        <!-- Content -->
        <div class="card__content">
            <h3 class="card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>

            <p class="card__excerpt">
                <?php echo esc_html( get_the_excerpt() ); ?>
            </p>

            <!-- Meta -->
            <div class="card__meta">
                <div class="card__author">
                    <?php echo get_avatar( get_the_author_meta( 'ID' ), 24, '', esc_attr( get_the_author() ), array( 'class' => 'card__author-avatar', 'loading' => 'lazy' ) ); ?>
                    <span class="card__author-name"><?php the_author(); ?></span>
                </div>
                <span class="card__date">
                    <?php mw_posted_on(); ?>
                </span>
                <span class="card__reading-time">
                    <?php mw_reading_time(); ?>
                </span>
            </div>
        </div>

    </article>

<?php endif; ?>
