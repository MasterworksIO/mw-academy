<?php
/**
 * Custom Post Type registrations for Masterworks Academy.
 *
 * @package MW_Content_Types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all Academy custom post types.
 */
function mw_register_academy_post_types() {

	$post_types = array(
		array(
			'slug'     => 'research-report',
			'singular' => 'Research Report',
			'plural'   => 'Research Reports',
			'icon'     => 'dashicons-analytics',
			'rewrite'  => 'academy/research',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		),
		array(
			'slug'     => 'artist-dossier',
			'singular' => 'Artist Dossier',
			'plural'   => 'Artist Dossiers',
			'icon'     => 'dashicons-art',
			'rewrite'  => 'academy/artists',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		),
		array(
			'slug'     => 'market-commentary',
			'singular' => 'Market Commentary',
			'plural'   => 'Market Commentaries',
			'icon'     => 'dashicons-megaphone',
			'rewrite'  => 'academy/commentary',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		),
		array(
			'slug'     => 'explainer',
			'singular' => 'Explainer',
			'plural'   => 'Explainers',
			'icon'     => 'dashicons-lightbulb',
			'rewrite'  => 'academy/explainers',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		),
		array(
			'slug'     => 'data-index',
			'singular' => 'Data & Index',
			'plural'   => 'Data & Indices',
			'icon'     => 'dashicons-chart-area',
			'rewrite'  => 'academy/data',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		),
		array(
			'slug'     => 'daily-news',
			'singular' => 'Daily News',
			'plural'   => 'Daily News',
			'icon'     => 'dashicons-rss',
			'rewrite'  => 'academy/news',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
		),
		array(
			'slug'     => 'white-paper',
			'singular' => 'White Paper',
			'plural'   => 'White Papers',
			'icon'     => 'dashicons-media-document',
			'rewrite'  => 'academy/white-papers',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		),
		array(
			'slug'     => 'cultural-update',
			'singular' => 'Cultural Update',
			'plural'   => 'Cultural Updates',
			'icon'     => 'dashicons-admin-customizer',
			'rewrite'  => 'academy/cultural',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		),
	);

	foreach ( $post_types as $pt ) {
		$labels = array(
			'name'                  => $pt['plural'],
			'singular_name'         => $pt['singular'],
			'add_new'               => 'Add New',
			'add_new_item'          => 'Add New ' . $pt['singular'],
			'edit_item'             => 'Edit ' . $pt['singular'],
			'new_item'              => 'New ' . $pt['singular'],
			'view_item'             => 'View ' . $pt['singular'],
			'view_items'            => 'View ' . $pt['plural'],
			'search_items'          => 'Search ' . $pt['plural'],
			'not_found'             => 'No ' . strtolower( $pt['plural'] ) . ' found.',
			'not_found_in_trash'    => 'No ' . strtolower( $pt['plural'] ) . ' found in Trash.',
			'all_items'             => 'All ' . $pt['plural'],
			'archives'              => $pt['singular'] . ' Archives',
			'attributes'            => $pt['singular'] . ' Attributes',
			'insert_into_item'      => 'Insert into ' . strtolower( $pt['singular'] ),
			'uploaded_to_this_item' => 'Uploaded to this ' . strtolower( $pt['singular'] ),
			'filter_items_list'     => 'Filter ' . strtolower( $pt['plural'] ) . ' list',
			'items_list_navigation' => $pt['plural'] . ' list navigation',
			'items_list'            => $pt['plural'] . ' list',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'mw-academy',
			'show_in_rest'       => true,
			'rest_base'          => $pt['slug'],
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => $pt['rewrite'],
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => $pt['icon'],
			'supports'           => $pt['supports'],
		);

		register_post_type( $pt['slug'], $args );
	}
}
add_action( 'init', 'mw_register_academy_post_types' );

/**
 * Return all Academy post type slugs.
 *
 * @return array
 */
function mw_get_academy_post_types() {
	return array(
		'research-report',
		'artist-dossier',
		'market-commentary',
		'explainer',
		'data-index',
		'daily-news',
		'white-paper',
		'cultural-update',
	);
}
