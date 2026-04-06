<?php
/**
 * Plugin Name: MW Content Types
 * Plugin URI:  https://masterworks.com
 * Description: Registers custom post types, taxonomies, and ACF field groups for the Masterworks Academy platform.
 * Version:     1.0.0
 * Author:      Masterworks
 * Author URI:  https://masterworks.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mw-content-types
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MW_CONTENT_TYPES_VERSION', '1.0.0' );
define( 'MW_CONTENT_TYPES_PATH', plugin_dir_path( __FILE__ ) );
define( 'MW_CONTENT_TYPES_URL', plugin_dir_url( __FILE__ ) );
define( 'MW_CONTENT_TYPES_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load plugin includes.
 */
function mw_content_types_load_includes() {
	require_once MW_CONTENT_TYPES_PATH . 'inc/post-types.php';
	require_once MW_CONTENT_TYPES_PATH . 'inc/taxonomies.php';
	require_once MW_CONTENT_TYPES_PATH . 'inc/acf-field-groups.php';
	require_once MW_CONTENT_TYPES_PATH . 'inc/admin-menu.php';
	require_once MW_CONTENT_TYPES_PATH . 'inc/rest-api.php';
}
add_action( 'plugins_loaded', 'mw_content_types_load_includes' );

/**
 * Plugin activation hook.
 */
function mw_content_types_activate() {
	// Load includes so post types and taxonomies are registered.
	mw_content_types_load_includes();
	mw_register_academy_post_types();
	mw_register_academy_taxonomies();

	// Insert default taxonomy terms.
	mw_insert_default_terms();

	// Flush rewrite rules after registering post types.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mw_content_types_activate' );

/**
 * Plugin deactivation hook.
 */
function mw_content_types_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'mw_content_types_deactivate' );

/**
 * Insert default taxonomy terms on activation.
 */
function mw_insert_default_terms() {
	$content_pillars = array(
		'Research',
		'Data & Indices',
		'Opinions & Explainers',
		'Daily News',
		'Cultural Updates',
	);
	foreach ( $content_pillars as $term ) {
		if ( ! term_exists( $term, 'content-pillar' ) ) {
			wp_insert_term( $term, 'content-pillar' );
		}
	}

	$audiences = array(
		'Existing Investors',
		'Prospective Investors',
		'Financial Advisors',
		'Media & Analysts',
	);
	foreach ( $audiences as $term ) {
		if ( ! term_exists( $term, 'audience' ) ) {
			wp_insert_term( $term, 'audience' );
		}
	}

	$art_segments = array(
		'Contemporary',
		'Post-War',
		'Ultra-Contemporary',
		'Impressionist',
		'Old Masters',
		'Photography',
		'Emerging',
	);
	foreach ( $art_segments as $term ) {
		if ( ! term_exists( $term, 'art-segment' ) ) {
			wp_insert_term( $term, 'art-segment' );
		}
	}
}
