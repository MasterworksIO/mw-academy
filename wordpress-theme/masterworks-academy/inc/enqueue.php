<?php
/**
 * Enqueue Scripts and Styles
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue frontend styles and scripts.
 */
function mw_academy_enqueue_scripts() {

    // Fonts are self-hosted via @font-face in style.css (Neue Haas Grotesk Display + Tiempos Headline)
    // No Google Fonts dependency — faster loading, no external requests

    // Main stylesheet
    wp_enqueue_style(
        'mw-academy-style',
        get_stylesheet_uri(),
        array(),
        MW_ACADEMY_VERSION
    );

    // Main JavaScript
    wp_enqueue_script(
        'mw-academy-main',
        MW_ACADEMY_URI . '/assets/js/main.js',
        array(),
        MW_ACADEMY_VERSION,
        true
    );

    // Localize script with AJAX data
    wp_localize_script( 'mw-academy-main', 'mwAcademy', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'mw_academy_nonce' ),
        'i18n'    => array(
            'loadMore'    => esc_html__( 'Load More Articles', 'masterworks-academy' ),
            'loading'     => esc_html__( 'Loading...', 'masterworks-academy' ),
            'noMore'      => esc_html__( 'No more articles', 'masterworks-academy' ),
            'linkCopied'  => esc_html__( 'Link copied!', 'masterworks-academy' ),
            'copyFailed'  => esc_html__( 'Copy failed', 'masterworks-academy' ),
        ),
    ) );

    // Conditionally load D3.js on posts with data viz blocks
    if ( is_singular() && mw_post_has_block_type( 'mw/data-viz' ) ) {
        wp_enqueue_script(
            'mw-d3',
            'https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js',
            array(),
            '7.0.0',
            true
        );

        wp_enqueue_script(
            'mw-data-viz',
            MW_ACADEMY_URI . '/assets/js/data-viz.js',
            array( 'mw-d3' ),
            MW_ACADEMY_VERSION,
            true
        );
    }

    // Conditionally load Three.js on posts with 3D blocks
    if ( is_singular() && mw_post_has_block_type( 'mw/three-d' ) ) {
        wp_enqueue_script(
            'mw-threejs',
            'https://cdn.jsdelivr.net/npm/three@0.160/build/three.module.min.js',
            array(),
            '0.160.0',
            true
        );

        wp_enqueue_script(
            'mw-three-d',
            MW_ACADEMY_URI . '/assets/js/three-d.js',
            array( 'mw-threejs' ),
            MW_ACADEMY_VERSION,
            true
        );
    }

    // Comment reply script (only on single posts with comments open)
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'mw_academy_enqueue_scripts' );

/**
 * Enqueue editor styles.
 */
function mw_academy_enqueue_editor_assets() {
    // Google Fonts for the editor
    wp_enqueue_style(
        'mw-academy-editor-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap',
        array(),
        null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
    );

    // Custom editor styles
    wp_enqueue_style(
        'mw-academy-editor-style',
        MW_ACADEMY_URI . '/assets/css/editor-style.css',
        array( 'mw-academy-editor-fonts' ),
        MW_ACADEMY_VERSION
    );
}
add_action( 'enqueue_block_editor_assets', 'mw_academy_enqueue_editor_assets' );

/**
 * Dequeue unnecessary default WordPress styles/scripts for performance.
 */
function mw_academy_dequeue_defaults() {
    // Remove WordPress emoji scripts
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );

    // Remove wp-embed script on non-singular pages
    if ( ! is_singular() ) {
        wp_deregister_script( 'wp-embed' );
    }

    // Remove Gutenberg frontend block library CSS on pages that don't need it
    // Keep on singular content where blocks are used
    if ( ! is_singular() && ! is_admin() ) {
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'wc-blocks-style' );
        wp_dequeue_style( 'global-styles' );
    }

    // Remove jQuery Migrate (keep jQuery for compatibility)
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
        wp_register_script( 'jquery', false, array(), '', true );
    }
}
add_action( 'wp_enqueue_scripts', 'mw_academy_dequeue_defaults', 100 );

/**
 * Remove emoji DNS prefetch.
 *
 * @param array  $urls          Array of resource URLs.
 * @param string $relation_type The relation type (e.g. 'dns-prefetch').
 * @return array Filtered URLs.
 */
function mw_academy_remove_emoji_dns_prefetch( $urls, $relation_type ) {
    if ( 'dns-prefetch' === $relation_type ) {
        $urls = array_filter( $urls, function( $url ) {
            return false === strpos( $url, 'https://s.w.org/images/core/emoji/' );
        } );
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'mw_academy_remove_emoji_dns_prefetch', 10, 2 );

/**
 * Add resource hints for performance (preconnect to Google Fonts, CDNs).
 *
 * @param array  $urls          Array of resource URLs.
 * @param string $relation_type The relation type.
 * @return array Filtered URLs.
 */
function mw_academy_resource_hints( $urls, $relation_type ) {
    if ( 'preconnect' === $relation_type ) {
        $urls[] = array(
            'href'        => 'https://fonts.googleapis.com',
            'crossorigin' => '',
        );
        $urls[] = array(
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        );
        $urls[] = array(
            'href'        => 'https://cdn.jsdelivr.net',
            'crossorigin' => '',
        );
    }
    return $urls;
}
add_filter( 'wp_resource_hints', 'mw_academy_resource_hints', 10, 2 );

/**
 * Check if the current post contains a specific block type.
 *
 * @param string $block_name The block name to check for (e.g., 'mw/data-viz').
 * @return bool True if the post contains the block.
 */
function mw_post_has_block_type( $block_name ) {
    if ( ! is_singular() ) {
        return false;
    }

    $post = get_post();
    if ( ! $post ) {
        return false;
    }

    // Check for the block in content
    if ( has_block( $block_name, $post ) ) {
        return true;
    }

    // Also check for custom meta flag (for ACF-based or meta-controlled loading)
    $load_flag = get_post_meta( $post->ID, '_mw_load_' . str_replace( '/', '_', $block_name ), true );
    return ! empty( $load_flag );
}

/**
 * Add async/defer attributes to specific scripts for performance.
 *
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string Modified script tag.
 */
function mw_academy_script_attributes( $tag, $handle ) {
    // Scripts to load with defer
    $defer_scripts = array( 'mw-data-viz', 'mw-three-d' );

    if ( in_array( $handle, $defer_scripts, true ) ) {
        return str_replace( ' src', ' defer src', $tag );
    }

    return $tag;
}
add_filter( 'script_loader_tag', 'mw_academy_script_attributes', 10, 2 );
