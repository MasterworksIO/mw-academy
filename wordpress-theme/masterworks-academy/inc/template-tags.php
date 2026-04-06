<?php
/**
 * Template Tags
 *
 * Custom template tags for the Masterworks Academy theme.
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the post date.
 *
 * @param string $format Optional. PHP date format. Defaults to theme setting or 'M j, Y'.
 */
function mw_posted_on( $format = '' ) {
    if ( empty( $format ) ) {
        $format = 'M j, Y';
    }

    $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';

    if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
        $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated screen-reader-text" datetime="%3$s">%4$s</time>';
    }

    printf(
        $time_string,
        esc_attr( get_the_date( DATE_W3C ) ),
        esc_html( get_the_date( $format ) ),
        esc_attr( get_the_modified_date( DATE_W3C ) ),
        esc_html( get_the_modified_date( $format ) )
    );
}

/**
 * Display the author with avatar.
 *
 * @param int $avatar_size Optional. Avatar size in pixels. Default 32.
 */
function mw_posted_by( $avatar_size = 32 ) {
    $author_id   = get_the_author_meta( 'ID' );
    $author_name = get_the_author();
    $author_url  = get_author_posts_url( $author_id );

    printf(
        '<span class="byline"><span class="author vcard">' .
        '%1$s<a class="url fn n" href="%2$s">%3$s</a>' .
        '</span></span>',
        get_avatar( $author_id, $avatar_size, '', esc_attr( $author_name ), array( 'class' => 'card__author-avatar' ) ),
        esc_url( $author_url ),
        esc_html( $author_name )
    );
}

/**
 * Display a category/pillar badge for the current post.
 *
 * Maps categories to badge styles. Falls back to a default style.
 */
function mw_category_badge() {
    $categories = get_the_category();
    if ( empty( $categories ) ) {
        return;
    }

    $category = $categories[0];
    $slug     = $category->slug;
    $name     = $category->name;

    // Map category slugs to badge modifier classes
    $badge_map = array(
        'research'     => 'research',
        'data-indices' => 'data',
        'data'         => 'data',
        'opinions'     => 'opinions',
        'daily-news'   => 'news',
        'news'         => 'news',
        'culture'      => 'culture',
        'white-papers' => 'white-papers',
    );

    $modifier = isset( $badge_map[ $slug ] ) ? $badge_map[ $slug ] : 'research';

    printf(
        '<span class="badge badge--%s">%s</span>',
        esc_attr( $modifier ),
        esc_html( $name )
    );
}

/**
 * Display estimated reading time for the current post.
 *
 * @param int $post_id Optional. Post ID. Defaults to current post.
 */
function mw_reading_time( $post_id = 0 ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    $content    = get_post_field( 'post_content', $post_id );
    $word_count = str_word_count( wp_strip_all_tags( $content ) );
    $minutes    = max( 1, (int) ceil( $word_count / 250 ) );

    printf(
        '<span class="reading-time">%s</span>',
        /* translators: %d: number of minutes */
        esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'masterworks-academy' ), $minutes ) )
    );
}

/**
 * Display an icon based on content type / category.
 *
 * Returns an SVG icon string for the post's primary category.
 */
function mw_content_type_icon() {
    $categories = get_the_category();
    if ( empty( $categories ) ) {
        return;
    }

    $slug = $categories[0]->slug;

    $icons = array(
        'research' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>',

        'data-indices' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',

        'opinions' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>',

        'daily-news' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6V7.5z"/></svg>',

        'culture' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>',

        'white-papers' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
    );

    $icon = isset( $icons[ $slug ] ) ? $icons[ $slug ] : $icons['research'];
    echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is hardcoded above
}

/**
 * Display social share buttons for the current post.
 */
function mw_share_buttons() {
    $post_url   = rawurlencode( get_the_permalink() );
    $post_title = rawurlencode( get_the_title() );
    ?>
    <div class="share-buttons">
        <span class="share-buttons__label"><?php esc_html_e( 'Share', 'masterworks-academy' ); ?></span>

        <!-- X / Twitter -->
        <a href="https://twitter.com/intent/tweet?url=<?php echo $post_url; ?>&text=<?php echo $post_title; ?>&via=masterworks"
           class="share-btn"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="<?php esc_attr_e( 'Share on X', 'masterworks-academy' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
        </a>

        <!-- LinkedIn -->
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $post_url; ?>"
           class="share-btn"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="<?php esc_attr_e( 'Share on LinkedIn', 'masterworks-academy' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
            </svg>
        </a>

        <!-- Email -->
        <a href="mailto:?subject=<?php echo $post_title; ?>&body=<?php echo $post_url; ?>"
           class="share-btn"
           aria-label="<?php esc_attr_e( 'Share via Email', 'masterworks-academy' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
        </a>

        <!-- Copy Link -->
        <button class="share-btn js-copy-link"
                data-url="<?php the_permalink(); ?>"
                aria-label="<?php esc_attr_e( 'Copy link', 'masterworks-academy' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.54a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L5.25 9.879" />
            </svg>
        </button>
    </div>
    <?php
}

/**
 * Get the primary category for a post.
 *
 * Checks for Yoast primary category first, falls back to first category.
 *
 * @param int $post_id Optional. Post ID.
 * @return WP_Term|false Category term object or false.
 */
function mw_get_primary_category( $post_id = 0 ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    // Check for Yoast primary category
    $primary_term_id = get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
    if ( $primary_term_id ) {
        $term = get_term( $primary_term_id, 'category' );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term;
        }
    }

    // Fallback to first category
    $categories = get_the_category( $post_id );
    if ( ! empty( $categories ) ) {
        return $categories[0];
    }

    return false;
}
