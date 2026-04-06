<?php
/**
 * Theme Header
 *
 * @package Masterworks_Academy
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'masterworks-academy' ); ?></a>

<header class="site-header" id="site-header" role="banner">
    <div class="site-header__inner">

        <!-- Logo -->
        <div class="site-header__brand">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__logo" rel="home">
                    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo-dark.png' ); ?>" alt="Masterworks" class="site-header__logo-img" height="20">
                </a>
            <?php endif; ?>
        </div>

        <!-- Main Navigation -->
        <nav class="site-header__nav" id="academy-nav" role="navigation" aria-label="<?php esc_attr_e( 'Academy Navigation', 'masterworks-academy' ); ?>">
            <?php
            if ( has_nav_menu( 'academy-main' ) ) {
                wp_nav_menu( array(
                    'theme_location' => 'academy-main',
                    'container'      => false,
                    'items_wrap'     => '%3$s',
                    'depth'          => 1,
                    'fallback_cb'    => false,
                ) );
            } else {
                // Default links when no menu is assigned
                $nav_items = array(
                    array( 'url' => 'https://masterworks.com/real-assets',    'label' => 'Real Assets' ),
                    array( 'url' => 'https://masterworks.com/how-it-works',   'label' => 'How It Works' ),
                    array( 'url' => 'https://masterworks.com/invest',         'label' => 'Invest' ),
                    array( 'url' => 'https://masterworks.com/price-database', 'label' => 'Price Database' ),
                    array( 'url' => 'https://masterworks.com/trading',        'label' => 'Trading' ),
                );
                foreach ( $nav_items as $item ) {
                    printf(
                        '<a href="%s">%s</a>',
                        esc_url( $item['url'] ),
                        esc_html( $item['label'] )
                    );
                }
            }
            ?>
        </nav>

        <!-- Actions -->
        <div class="site-header__actions">
            <!-- User Avatar -->
            <a href="https://masterworks.com/dashboard" class="site-header__avatar" aria-label="My Account">
                <span class="site-header__avatar-initials">JN</span>
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="site-header__menu-toggle" id="menu-toggle" aria-label="<?php esc_attr_e( 'Toggle menu', 'masterworks-academy' ); ?>" aria-expanded="false" aria-controls="academy-nav">
                <div class="hamburger" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>
        </div>

    </div>
</header>

<?php
// Breadcrumbs (Yoast SEO or fallback)
if ( ! is_front_page() ) : ?>
    <div class="breadcrumbs">
        <div class="container">
            <?php
            if ( function_exists( 'yoast_breadcrumb' ) ) {
                yoast_breadcrumb( '<nav aria-label="' . esc_attr__( 'Breadcrumb', 'masterworks-academy' ) . '">', '</nav>' );
            } else {
                // Simple fallback breadcrumbs
                echo '<nav aria-label="' . esc_attr__( 'Breadcrumb', 'masterworks-academy' ) . '">';
                echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Academy', 'masterworks-academy' ) . '</a>';
                echo '<span aria-hidden="true"> / </span>';
                if ( is_category() ) {
                    single_cat_title();
                } elseif ( is_single() ) {
                    $categories = get_the_category();
                    if ( ! empty( $categories ) ) {
                        echo '<a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '">' . esc_html( $categories[0]->name ) . '</a>';
                        echo '<span aria-hidden="true"> / </span>';
                    }
                    echo '<span class="breadcrumb_last">' . esc_html( get_the_title() ) . '</span>';
                } elseif ( is_page() ) {
                    echo '<span class="breadcrumb_last">' . esc_html( get_the_title() ) . '</span>';
                } elseif ( is_search() ) {
                    /* translators: %s: search query */
                    echo '<span class="breadcrumb_last">' . sprintf( esc_html__( 'Search: %s', 'masterworks-academy' ), get_search_query() ) . '</span>';
                }
                echo '</nav>';
            }
            ?>
        </div>
    </div>
<?php endif; ?>

<main id="main-content" class="site-main" role="main">
