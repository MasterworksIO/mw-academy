<?php
/**
 * Institutional Card — clean, consistent format
 *
 * @package Masterworks_Academy
 * @since 3.0.0
 */
?>
<a href="<?php the_permalink(); ?>" class="ins-card">
    <div class="ins-card__image">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'medium_large' ); ?>
        <?php else : ?>
            <div class="ins-placeholder">MASTERWORKS</div>
        <?php endif; ?>
    </div>
    <div class="ins-card__body">
        <div class="ins-label"><?php echo esc_html( strtoupper( get_post_type_object( get_post_type() )->labels->singular_name ) ); ?></div>
        <h3 class="ins-card__title"><?php the_title(); ?></h3>
        <p class="ins-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
        <div class="ins-card__date"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></div>
    </div>
</a>
