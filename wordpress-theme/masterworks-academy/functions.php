<?php
/**
 * Masterworks Academy Theme Functions
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Use local avatar image instead of Gravatar.
 */
add_filter( 'get_avatar_url', function( $url, $id_or_email ) {
    $user_id = 0;
    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
        $user_id = (int) $id_or_email->user_id;
    } elseif ( is_string( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        if ( $user ) { $user_id = $user->ID; }
    }

    if ( $user_id ) {
        $local = get_user_meta( $user_id, 'simple_local_avatar', true );
        if ( ! empty( $local['media_id'] ) ) {
            $img_url = wp_get_attachment_url( $local['media_id'] );
            if ( $img_url ) { return $img_url; }
        }
    }
    return $url;
}, 10, 2 );

/**
 * Allow SVG tags in post content (for inline data visualizations).
 */
add_filter( 'wp_kses_allowed_html', function( $tags, $context ) {
    if ( 'post' === $context ) {
        $tags['svg'] = array(
            'viewbox' => true, 'preserveaspectratio' => true, 'style' => true,
            'class' => true, 'xmlns' => true, 'width' => true, 'height' => true,
            'fill' => true, 'stroke' => true,
        );
        $tags['polyline'] = array( 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'points' => true, 'stroke-linejoin' => true, 'stroke-dasharray' => true, 'stroke-opacity' => true );
        $tags['polygon'] = array( 'fill' => true, 'points' => true );
        $tags['line'] = array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-dasharray' => true );
        $tags['circle'] = array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true );
        $tags['rect'] = array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'fill' => true, 'rx' => true, 'ry' => true, 'stroke' => true, 'stroke-width' => true );
        $tags['text'] = array( 'x' => true, 'y' => true, 'fill' => true, 'font-size' => true, 'font-family' => true, 'font-weight' => true, 'text-anchor' => true, 'opacity' => true, 'style' => true );
        $tags['defs'] = array();
        $tags['lineargradient'] = array( 'id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true );
        $tags['stop'] = array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true, 'style' => true );
        $tags['path'] = array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true );
        $tags['g'] = array( 'transform' => true, 'fill' => true, 'stroke' => true );
        $tags['iframe'] = array( 'src' => true, 'title' => true, 'frameborder' => true, 'allow' => true, 'allowfullscreen' => true, 'loading' => true, 'style' => true, 'width' => true, 'height' => true );
    }
    return $tags;
}, 10, 2 );

/**
 * Theme constants
 */
define( 'MW_ACADEMY_VERSION', '1.0.0' );
define( 'MW_ACADEMY_DIR', get_template_directory() );
define( 'MW_ACADEMY_URI', get_template_directory_uri() );

/**
 * Theme setup
 */
function mw_academy_setup() {
    // Make theme available for translation
    load_theme_textdomain( 'masterworks-academy', MW_ACADEMY_DIR . '/languages' );

    // Add default posts and comments RSS feed links to head
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails
    add_theme_support( 'post-thumbnails' );

    // Custom image sizes
    add_image_size( 'mw-card-thumb', 600, 375, true );
    add_image_size( 'mw-card-thumb-lg', 800, 500, true );
    add_image_size( 'mw-hero', 1200, 675, true );
    add_image_size( 'mw-featured', 1440, 560, true );
    add_image_size( 'mw-author-avatar', 160, 160, true );

    // Register navigation menus
    register_nav_menus( array(
        'primary'      => esc_html__( 'Primary Menu', 'masterworks-academy' ),
        'academy-main' => esc_html__( 'Academy Main Navigation', 'masterworks-academy' ),
        'footer'       => esc_html__( 'Footer Menu', 'masterworks-academy' ),
    ) );

    // HTML5 support
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
        'navigation-widgets',
    ) );

    // Custom logo support
    add_theme_support( 'custom-logo', array(
        'height'      => 64,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Add support for responsive embedded content
    add_theme_support( 'responsive-embeds' );

    // Add support for wide and full-width alignment in Gutenberg
    add_theme_support( 'align-wide' );

    // Editor styles
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // Block editor color palette
    add_theme_support( 'editor-color-palette', array(
        array(
            'name'  => esc_html__( 'Masterworks Purple', 'masterworks-academy' ),
            'slug'  => 'mw-purple',
            'color' => '#6B2FA0',
        ),
        array(
            'name'  => esc_html__( 'Navy', 'masterworks-academy' ),
            'slug'  => 'mw-navy',
            'color' => '#1A1A2E',
        ),
        array(
            'name'  => esc_html__( 'Gold', 'masterworks-academy' ),
            'slug'  => 'mw-gold',
            'color' => '#C9A227',
        ),
        array(
            'name'  => esc_html__( 'Background', 'masterworks-academy' ),
            'slug'  => 'mw-bg',
            'color' => '#FAFAFA',
        ),
        array(
            'name'  => esc_html__( 'White', 'masterworks-academy' ),
            'slug'  => 'mw-white',
            'color' => '#FFFFFF',
        ),
    ) );

    // Disable custom colors in block editor
    add_theme_support( 'disable-custom-colors' );

    // Block styles
    add_theme_support( 'wp-block-styles' );

    // Add support for custom line height
    add_theme_support( 'custom-line-height' );

    // Add support for custom spacing
    add_theme_support( 'custom-spacing' );

    // Appearance tools
    add_theme_support( 'appearance-tools' );
}
add_action( 'after_setup_theme', 'mw_academy_setup' );

/**
 * Set the content width based on the theme design.
 */
function mw_academy_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'mw_academy_content_width', 768 );
}
add_action( 'after_setup_theme', 'mw_academy_content_width', 0 );

/**
 * Register widget areas / sidebars
 */
function mw_academy_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Academy Sidebar', 'masterworks-academy' ),
        'id'            => 'academy-sidebar',
        'description'   => esc_html__( 'Sidebar for Academy pages and articles.', 'masterworks-academy' ),
        'before_widget' => '<div id="%1$s" class="sidebar__section widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="sidebar__title widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Academy Footer Widgets', 'masterworks-academy' ),
        'id'            => 'academy-footer-widgets',
        'description'   => esc_html__( 'Footer widget area for Academy.', 'masterworks-academy' ),
        'before_widget' => '<div id="%1$s" class="site-footer__widget widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="site-footer__column-title widget-title">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'mw_academy_widgets_init' );

/**
 * Include required files
 */
require MW_ACADEMY_DIR . '/inc/enqueue.php';
require MW_ACADEMY_DIR . '/inc/template-tags.php';
require MW_ACADEMY_DIR . '/inc/customizer.php';

/**
 * Register custom Gutenberg block styles
 */
function mw_academy_register_block_styles() {
    register_block_style( 'core/quote', array(
        'name'  => 'mw-pullquote',
        'label' => esc_html__( 'Masterworks Pullquote', 'masterworks-academy' ),
    ) );

    register_block_style( 'core/image', array(
        'name'  => 'mw-rounded',
        'label' => esc_html__( 'Rounded Corners', 'masterworks-academy' ),
    ) );

    register_block_style( 'core/group', array(
        'name'  => 'mw-card',
        'label' => esc_html__( 'Card Style', 'masterworks-academy' ),
    ) );

    register_block_style( 'core/table', array(
        'name'  => 'mw-data-table',
        'label' => esc_html__( 'Data Table', 'masterworks-academy' ),
    ) );

    register_block_style( 'core/separator', array(
        'name'  => 'mw-gold-rule',
        'label' => esc_html__( 'Gold Rule', 'masterworks-academy' ),
    ) );
}
add_action( 'init', 'mw_academy_register_block_styles' );

/**
 * Register block patterns
 */
function mw_academy_register_block_patterns() {
    register_block_pattern_category( 'masterworks', array(
        'label' => esc_html__( 'Masterworks Academy', 'masterworks-academy' ),
    ) );

    // Data callout pattern
    register_block_pattern( 'masterworks/data-callout', array(
        'title'       => esc_html__( 'Data Callout', 'masterworks-academy' ),
        'description' => esc_html__( 'A highlighted data point with context.', 'masterworks-academy' ),
        'categories'  => array( 'masterworks' ),
        'content'     => '<!-- wp:group {"className":"mw-data-callout","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"left":{"color":"#6B2FA0","width":"4px"}}}} -->
<div class="wp-block-group mw-data-callout">
<!-- wp:paragraph {"style":{"typography":{"fontSize":"3rem","fontWeight":"700"}},"textColor":"mw-purple"} -->
<p class="has-mw-purple-color has-text-color" style="font-size:3rem;font-weight:700">$1.7T</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"textColor":"mw-navy"} -->
<p class="has-mw-navy-color has-text-color">Estimated global art market value, representing significant investment opportunity.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
    ) );

    // Artist spotlight pattern
    register_block_pattern( 'masterworks/artist-spotlight', array(
        'title'       => esc_html__( 'Artist Spotlight', 'masterworks-academy' ),
        'description' => esc_html__( 'Featured artist card with image and bio.', 'masterworks-academy' ),
        'categories'  => array( 'masterworks' ),
        'content'     => '<!-- wp:group {"className":"mw-artist-spotlight","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#f0f0f5"},"border":{"radius":"12px"}}} -->
<div class="wp-block-group mw-artist-spotlight" style="border-radius:12px;background-color:#f0f0f5">
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<!-- wp:image {"className":"is-style-mw-rounded"} -->
<figure class="wp-block-image is-style-mw-rounded"><img src="" alt="Artist portrait"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Artist Name</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.875rem"}},"textColor":"mw-purple"} -->
<p class="has-mw-purple-color has-text-color" style="font-size:0.875rem">American, b. 1960 | Contemporary</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Brief artist biography and market context goes here.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
    ) );

    // CTA banner pattern
    register_block_pattern( 'masterworks/cta-banner', array(
        'title'       => esc_html__( 'Investment CTA Banner', 'masterworks-academy' ),
        'description' => esc_html__( 'Call to action banner for Masterworks investing.', 'masterworks-academy' ),
        'categories'  => array( 'masterworks' ),
        'content'     => '<!-- wp:group {"className":"mw-cta-banner","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"radius":"16px"}},"backgroundColor":"mw-navy"} -->
<div class="wp-block-group mw-cta-banner has-mw-navy-background-color has-background" style="border-radius:16px">
<!-- wp:heading {"textAlign":"center","style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="color:#ffffff">Invest in art like the ultra-wealthy</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"rgba(255,255,255,0.7)"}}} -->
<p class="has-text-align-center" style="color:rgba(255,255,255,0.7)">Masterworks lets you invest in shares of multi-million dollar artworks by names like Banksy, Basquiat, and Picasso.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"style":{"color":{"background":"#C9A227","text":"#1A1A2E"},"border":{"radius":"8px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background" style="border-radius:8px;color:#1A1A2E;background-color:#C9A227">Start Investing</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
    ) );
}
add_action( 'init', 'mw_academy_register_block_patterns' );

/**
 * AJAX handler for filtering posts
 */
function mw_academy_filter_posts() {
    check_ajax_referer( 'mw_academy_nonce', 'nonce' );

    $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
    $paged    = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'paged'          => $paged,
    );

    if ( ! empty( $category ) && 'all' !== $category ) {
        $args['category_name'] = $category;
    }

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        ob_start();
        while ( $query->have_posts() ) {
            $query->the_post();
            get_template_part( 'template-parts/content', 'card' );
        }
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'      => $html,
            'max_pages' => $query->max_num_pages,
            'found'     => $query->found_posts,
        ) );
    } else {
        wp_send_json_success( array(
            'html'      => '<p class="no-results">' . esc_html__( 'No articles found.', 'masterworks-academy' ) . '</p>',
            'max_pages' => 0,
            'found'     => 0,
        ) );
    }

    wp_reset_postdata();
    wp_die();
}
add_action( 'wp_ajax_mw_filter_posts', 'mw_academy_filter_posts' );
add_action( 'wp_ajax_nopriv_mw_filter_posts', 'mw_academy_filter_posts' );

/**
 * Modify main query for front page
 */
function mw_academy_pre_get_posts( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        if ( is_home() || is_front_page() ) {
            $query->set( 'posts_per_page', 12 );
        }
    }
}
add_action( 'pre_get_posts', 'mw_academy_pre_get_posts' );

/**
 * Add custom image sizes to media selector
 */
function mw_academy_custom_image_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'mw-card-thumb'    => esc_html__( 'Card Thumbnail', 'masterworks-academy' ),
        'mw-card-thumb-lg' => esc_html__( 'Card Thumbnail Large', 'masterworks-academy' ),
        'mw-hero'          => esc_html__( 'Hero Image', 'masterworks-academy' ),
    ) );
}
add_filter( 'image_size_names_choose', 'mw_academy_custom_image_sizes' );

/**
 * Custom excerpt length
 */
function mw_academy_excerpt_length( $length ) {
    if ( is_admin() ) {
        return $length;
    }
    return 24;
}
add_filter( 'excerpt_length', 'mw_academy_excerpt_length' );

/**
 * Custom excerpt more text
 */
function mw_academy_excerpt_more( $more ) {
    if ( is_admin() ) {
        return $more;
    }
    return '&hellip;';
}
add_filter( 'excerpt_more', 'mw_academy_excerpt_more' );
