<?php
/**
 * Custom Taxonomy registrations for Masterworks Academy.
 *
 * @package MW_Content_Types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all Academy taxonomies.
 */
function mw_register_academy_taxonomies() {

	$all_post_types = mw_get_academy_post_types();

	$taxonomies = array(
		array(
			'slug'         => 'content-pillar',
			'singular'     => 'Content Pillar',
			'plural'       => 'Content Pillars',
			'post_types'   => $all_post_types,
			'hierarchical' => true,
		),
		array(
			'slug'         => 'audience',
			'singular'     => 'Audience',
			'plural'       => 'Audiences',
			'post_types'   => $all_post_types,
			'hierarchical' => true,
		),
		array(
			'slug'         => 'artist-name',
			'singular'     => 'Artist',
			'plural'       => 'Artists',
			'post_types'   => array( 'artist-dossier', 'market-commentary', 'data-index', 'research-report' ),
			'hierarchical' => false,
		),
		array(
			'slug'         => 'art-segment',
			'singular'     => 'Art Segment',
			'plural'       => 'Art Segments',
			'post_types'   => array( 'research-report', 'data-index', 'market-commentary' ),
			'hierarchical' => true,
		),
		array(
			'slug'         => 'expert-voice',
			'singular'     => 'Expert Voice',
			'plural'       => 'Expert Voices',
			'post_types'   => $all_post_types,
			'hierarchical' => false,
		),
	);

	foreach ( $taxonomies as $tax ) {
		$labels = array(
			'name'                       => $tax['plural'],
			'singular_name'              => $tax['singular'],
			'search_items'               => 'Search ' . $tax['plural'],
			'popular_items'              => 'Popular ' . $tax['plural'],
			'all_items'                  => 'All ' . $tax['plural'],
			'parent_item'                => $tax['hierarchical'] ? 'Parent ' . $tax['singular'] : null,
			'parent_item_colon'          => $tax['hierarchical'] ? 'Parent ' . $tax['singular'] . ':' : null,
			'edit_item'                  => 'Edit ' . $tax['singular'],
			'view_item'                  => 'View ' . $tax['singular'],
			'update_item'                => 'Update ' . $tax['singular'],
			'add_new_item'               => 'Add New ' . $tax['singular'],
			'new_item_name'              => 'New ' . $tax['singular'] . ' Name',
			'separate_items_with_commas' => 'Separate ' . strtolower( $tax['plural'] ) . ' with commas',
			'add_or_remove_items'        => 'Add or remove ' . strtolower( $tax['plural'] ),
			'choose_from_most_used'      => 'Choose from the most used ' . strtolower( $tax['plural'] ),
			'not_found'                  => 'No ' . strtolower( $tax['plural'] ) . ' found.',
			'no_terms'                   => 'No ' . strtolower( $tax['plural'] ),
			'items_list_navigation'      => $tax['plural'] . ' list navigation',
			'items_list'                 => $tax['plural'] . ' list',
			'back_to_items'              => '&larr; Go to ' . $tax['plural'],
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => $tax['hierarchical'],
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'show_tagcloud'     => ! $tax['hierarchical'],
			'rewrite'           => array(
				'slug'       => 'academy/' . $tax['slug'],
				'with_front' => false,
			),
		);

		register_taxonomy( $tax['slug'], $tax['post_types'], $args );
	}
}
add_action( 'init', 'mw_register_academy_taxonomies' );
