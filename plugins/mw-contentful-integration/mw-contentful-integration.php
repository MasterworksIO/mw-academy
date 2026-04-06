<?php
/**
 * Plugin Name: MW Contentful Integration
 * Plugin URI:  https://masterworks.com
 * Description: Integrates Contentful CMS image assets with WordPress for the Masterworks Academy platform. Browse, select, and render optimized images via Contentful CDN.
 * Version:     1.0.0
 * Author:      Masterworks
 * Author URI:  https://masterworks.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mw-contentful-integration
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MW_CONTENTFUL_VERSION', '1.0.0' );
define( 'MW_CONTENTFUL_PATH', plugin_dir_path( __FILE__ ) );
define( 'MW_CONTENTFUL_URL', plugin_dir_url( __FILE__ ) );
define( 'MW_CONTENTFUL_BASENAME', plugin_basename( __FILE__ ) );

// Contentful API base URLs.
define( 'MW_CONTENTFUL_CDN_BASE', 'https://cdn.contentful.com' );
define( 'MW_CONTENTFUL_IMAGES_BASE', 'https://images.ctfassets.net' );
define( 'MW_CONTENTFUL_PREVIEW_BASE', 'https://preview.contentful.com' );

/**
 * Retrieve Contentful credentials from Academy settings.
 *
 * @return array {
 *     @type string $space_id     Contentful Space ID.
 *     @type string $access_token Content Delivery API access token.
 * }
 */
function mw_contentful_get_credentials() {
	$options = get_option( 'mw_academy_options', array() );

	return array(
		'space_id'     => isset( $options['contentful_space_id'] ) ? $options['contentful_space_id'] : '',
		'access_token' => isset( $options['contentful_access_token'] ) ? $options['contentful_access_token'] : '',
	);
}

/**
 * Load plugin includes.
 */
function mw_contentful_load_includes() {
	require_once MW_CONTENTFUL_PATH . 'inc/api-client.php';
	require_once MW_CONTENTFUL_PATH . 'inc/template-functions.php';
	require_once MW_CONTENTFUL_PATH . 'inc/shortcode.php';
	require_once MW_CONTENTFUL_PATH . 'inc/admin-page.php';
	require_once MW_CONTENTFUL_PATH . 'inc/gutenberg-block.php';

	// Load ACF field type only when ACF is active.
	if ( class_exists( 'ACF' ) ) {
		require_once MW_CONTENTFUL_PATH . 'inc/acf-field.php';
	}
}
add_action( 'plugins_loaded', 'mw_contentful_load_includes' );

/**
 * Enqueue admin assets on relevant pages.
 *
 * @param string $hook_suffix The current admin page hook suffix.
 */
function mw_contentful_admin_enqueue( $hook_suffix ) {
	// Load on our admin page and on post edit screens (for ACF fields).
	$load_on = array(
		'academy_page_mw-contentful-media',
		'post.php',
		'post-new.php',
	);

	if ( ! in_array( $hook_suffix, $load_on, true ) ) {
		return;
	}

	wp_enqueue_style(
		'mw-contentful-admin',
		MW_CONTENTFUL_URL . 'assets/css/admin-styles.css',
		array(),
		MW_CONTENTFUL_VERSION
	);

	wp_enqueue_script(
		'mw-contentful-admin',
		MW_CONTENTFUL_URL . 'assets/js/admin-media-browser.js',
		array( 'jquery' ),
		MW_CONTENTFUL_VERSION,
		true
	);

	wp_localize_script( 'mw-contentful-admin', 'mwContentful', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'mw_contentful_nonce' ),
		'imgBase' => MW_CONTENTFUL_IMAGES_BASE,
	) );
}
add_action( 'admin_enqueue_scripts', 'mw_contentful_admin_enqueue' );

/**
 * Plugin activation hook.
 */
function mw_contentful_activate() {
	// Flush rewrite rules for any custom endpoints.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mw_contentful_activate' );

/**
 * Plugin deactivation hook.
 */
function mw_contentful_deactivate() {
	// Clean up transients.
	global $wpdb;
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mw_ctfl_%' OR option_name LIKE '_transient_timeout_mw_ctfl_%'"
	);

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'mw_contentful_deactivate' );
