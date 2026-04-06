<?php
/**
 * Custom REST API endpoints for Masterworks Academy.
 *
 * @package MW_Content_Types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom REST API routes.
 */
function mw_register_academy_rest_routes() {
	$namespace = 'mw-academy/v1';

	register_rest_route( $namespace, '/content', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'mw_rest_get_content',
		'permission_callback' => '__return_true',
		'args'                => mw_get_content_endpoint_args(),
	) );

	register_rest_route( $namespace, '/artists', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'mw_rest_get_artists',
		'permission_callback' => '__return_true',
		'args'                => mw_get_artists_endpoint_args(),
	) );

	register_rest_route( $namespace, '/indices', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'mw_rest_get_indices',
		'permission_callback' => '__return_true',
		'args'                => mw_get_indices_endpoint_args(),
	) );
}
add_action( 'rest_api_init', 'mw_register_academy_rest_routes' );

/**
 * Shared query argument definitions for taxonomy, date, search, and pagination filters.
 *
 * @return array
 */
function mw_get_shared_filter_args() {
	return array(
		'content_pillar' => array(
			'description'       => 'Filter by content-pillar taxonomy slug.',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'audience'        => array(
			'description'       => 'Filter by audience taxonomy slug.',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'art_segment'     => array(
			'description'       => 'Filter by art-segment taxonomy slug.',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'artist_name'     => array(
			'description'       => 'Filter by artist-name taxonomy slug.',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'expert_voice'    => array(
			'description'       => 'Filter by expert-voice taxonomy slug.',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'after'           => array(
			'description'       => 'Return items published after this date (YYYY-MM-DD).',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'before'          => array(
			'description'       => 'Return items published before this date (YYYY-MM-DD).',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'search'          => array(
			'description'       => 'Search keyword.',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'per_page'        => array(
			'description'       => 'Number of items per page.',
			'type'              => 'integer',
			'default'           => 10,
			'sanitize_callback' => 'absint',
		),
		'page'            => array(
			'description'       => 'Current page number.',
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
		),
	);
}

/**
 * Build a tax_query array from request parameters.
 *
 * @param WP_REST_Request $request The REST request.
 * @return array
 */
function mw_build_tax_query( $request ) {
	$tax_query = array();
	$mappings  = array(
		'content_pillar' => 'content-pillar',
		'audience'       => 'audience',
		'art_segment'    => 'art-segment',
		'artist_name'    => 'artist-name',
		'expert_voice'   => 'expert-voice',
	);

	foreach ( $mappings as $param => $taxonomy ) {
		$value = $request->get_param( $param );
		if ( ! empty( $value ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => explode( ',', $value ),
			);
		}
	}

	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	return $tax_query;
}

/**
 * Build a date_query array from request parameters.
 *
 * @param WP_REST_Request $request The REST request.
 * @return array
 */
function mw_build_date_query( $request ) {
	$date_query = array();
	$after      = $request->get_param( 'after' );
	$before     = $request->get_param( 'before' );

	if ( ! empty( $after ) ) {
		$date_query['after'] = $after;
	}
	if ( ! empty( $before ) ) {
		$date_query['before'] = $before;
	}

	return ! empty( $date_query ) ? array( $date_query ) : array();
}

/**
 * Format a post for REST API response.
 *
 * @param WP_Post $post  The post object.
 * @param array   $extra Additional fields to merge.
 * @return array
 */
function mw_format_rest_post( $post, $extra = array() ) {
	$thumbnail_id  = get_post_thumbnail_id( $post->ID );
	$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'full' ) : null;

	$taxonomies = array( 'content-pillar', 'audience', 'art-segment', 'artist-name', 'expert-voice' );
	$terms_data = array();
	foreach ( $taxonomies as $tax ) {
		$terms = wp_get_post_terms( $post->ID, $tax, array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$terms_data[ $tax ] = $terms;
		}
	}

	$base = array(
		'id'             => $post->ID,
		'title'          => get_the_title( $post ),
		'slug'           => $post->post_name,
		'post_type'      => $post->post_type,
		'date'           => $post->post_date,
		'modified'       => $post->post_modified,
		'excerpt'        => get_the_excerpt( $post ),
		'link'           => get_permalink( $post ),
		'featured_image' => $thumbnail_url,
		'taxonomies'     => $terms_data,
	);

	return array_merge( $base, $extra );
}

/**
 * Add pagination headers to a REST response.
 *
 * @param WP_REST_Response $response   The REST response.
 * @param WP_Query         $query      The WP_Query instance.
 * @param WP_REST_Request  $request    The REST request.
 * @return WP_REST_Response
 */
function mw_add_pagination_headers( $response, $query, $request ) {
	$total       = $query->found_posts;
	$total_pages = $query->max_num_pages;

	$response->header( 'X-WP-Total', $total );
	$response->header( 'X-WP-TotalPages', $total_pages );

	return $response;
}

// -------------------------------------------------------------------------
// Content endpoint.
// -------------------------------------------------------------------------

/**
 * Args for /content endpoint.
 *
 * @return array
 */
function mw_get_content_endpoint_args() {
	$args = mw_get_shared_filter_args();

	$args['type'] = array(
		'description'       => 'Filter by post type slug. Comma-separated for multiple.',
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	);

	return $args;
}

/**
 * Handle GET /content request.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response
 */
function mw_rest_get_content( $request ) {
	$post_types = mw_get_academy_post_types();
	$type_param = $request->get_param( 'type' );

	if ( ! empty( $type_param ) ) {
		$requested  = array_map( 'trim', explode( ',', $type_param ) );
		$post_types = array_intersect( $requested, $post_types );
		if ( empty( $post_types ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid post type.' ), 400 );
		}
	}

	$query_args = array(
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'posts_per_page' => $request->get_param( 'per_page' ),
		'paged'          => $request->get_param( 'page' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$tax_query = mw_build_tax_query( $request );
	if ( ! empty( $tax_query ) ) {
		$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$date_query = mw_build_date_query( $request );
	if ( ! empty( $date_query ) ) {
		$query_args['date_query'] = $date_query;
	}

	$search = $request->get_param( 'search' );
	if ( ! empty( $search ) ) {
		$query_args['s'] = $search;
	}

	$query = new WP_Query( $query_args );
	$items = array();

	foreach ( $query->posts as $post ) {
		$items[] = mw_format_rest_post( $post );
	}

	$response = new WP_REST_Response( $items, 200 );
	return mw_add_pagination_headers( $response, $query, $request );
}

// -------------------------------------------------------------------------
// Artists endpoint.
// -------------------------------------------------------------------------

/**
 * Args for /artists endpoint.
 *
 * @return array
 */
function mw_get_artists_endpoint_args() {
	$args = mw_get_shared_filter_args();

	$args['market_trajectory'] = array(
		'description'       => 'Filter by market trajectory (Rising, Stable, Cooling, Volatile).',
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	);

	$args['medium'] = array(
		'description'       => 'Filter by medium.',
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	);

	return $args;
}

/**
 * Handle GET /artists request.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response
 */
function mw_rest_get_artists( $request ) {
	$query_args = array(
		'post_type'      => 'artist-dossier',
		'post_status'    => 'publish',
		'posts_per_page' => $request->get_param( 'per_page' ),
		'paged'          => $request->get_param( 'page' ),
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$meta_query = array();

	$trajectory = $request->get_param( 'market_trajectory' );
	if ( ! empty( $trajectory ) ) {
		$meta_query[] = array(
			'key'     => 'market_trajectory',
			'value'   => sanitize_text_field( $trajectory ),
			'compare' => '=',
		);
	}

	$medium = $request->get_param( 'medium' );
	if ( ! empty( $medium ) ) {
		$meta_query[] = array(
			'key'     => 'medium',
			'value'   => sanitize_text_field( $medium ),
			'compare' => '=',
		);
	}

	if ( ! empty( $meta_query ) ) {
		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
		}
		$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	$tax_query = mw_build_tax_query( $request );
	if ( ! empty( $tax_query ) ) {
		$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$date_query = mw_build_date_query( $request );
	if ( ! empty( $date_query ) ) {
		$query_args['date_query'] = $date_query;
	}

	$search = $request->get_param( 'search' );
	if ( ! empty( $search ) ) {
		$query_args['s'] = $search;
	}

	$query = new WP_Query( $query_args );
	$items = array();

	foreach ( $query->posts as $post ) {
		$acf_fields = array();
		if ( function_exists( 'get_fields' ) ) {
			$acf_fields = get_fields( $post->ID );
		}

		$artist_data = array(
			'artist_id'          => isset( $acf_fields['artist_id'] ) ? (int) $acf_fields['artist_id'] : null,
			'birth_year'         => isset( $acf_fields['birth_year'] ) ? (int) $acf_fields['birth_year'] : null,
			'death_year'         => isset( $acf_fields['death_year'] ) ? (int) $acf_fields['death_year'] : null,
			'nationality'        => isset( $acf_fields['nationality'] ) ? $acf_fields['nationality'] : null,
			'medium'             => isset( $acf_fields['medium'] ) ? $acf_fields['medium'] : null,
			'market_trajectory'  => isset( $acf_fields['market_trajectory'] ) ? $acf_fields['market_trajectory'] : null,
			'price_range_low'    => isset( $acf_fields['price_range_low'] ) ? (float) $acf_fields['price_range_low'] : null,
			'price_range_high'   => isset( $acf_fields['price_range_high'] ) ? (float) $acf_fields['price_range_high'] : null,
			'total_auction_lots' => isset( $acf_fields['total_auction_lots'] ) ? (int) $acf_fields['total_auction_lots'] : null,
			'avg_annual_return'  => isset( $acf_fields['avg_annual_return'] ) ? (float) $acf_fields['avg_annual_return'] : null,
			'key_metrics'        => isset( $acf_fields['key_metrics'] ) ? $acf_fields['key_metrics'] : array(),
			'notable_sales'      => isset( $acf_fields['notable_sales'] ) ? $acf_fields['notable_sales'] : array(),
		);

		$items[] = mw_format_rest_post( $post, $artist_data );
	}

	$response = new WP_REST_Response( $items, 200 );
	return mw_add_pagination_headers( $response, $query, $request );
}

// -------------------------------------------------------------------------
// Indices endpoint.
// -------------------------------------------------------------------------

/**
 * Args for /indices endpoint.
 *
 * @return array
 */
function mw_get_indices_endpoint_args() {
	return mw_get_shared_filter_args();
}

/**
 * Handle GET /indices request.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response
 */
function mw_rest_get_indices( $request ) {
	$query_args = array(
		'post_type'      => 'data-index',
		'post_status'    => 'publish',
		'posts_per_page' => $request->get_param( 'per_page' ),
		'paged'          => $request->get_param( 'page' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$tax_query = mw_build_tax_query( $request );
	if ( ! empty( $tax_query ) ) {
		$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$date_query = mw_build_date_query( $request );
	if ( ! empty( $date_query ) ) {
		$query_args['date_query'] = $date_query;
	}

	$search = $request->get_param( 'search' );
	if ( ! empty( $search ) ) {
		$query_args['s'] = $search;
	}

	$query = new WP_Query( $query_args );
	$items = array();

	foreach ( $query->posts as $post ) {
		$acf_fields = array();
		if ( function_exists( 'get_fields' ) ) {
			$acf_fields = get_fields( $post->ID );
		}

		$index_data = array(
			'report_type'          => isset( $acf_fields['report_type'] ) ? $acf_fields['report_type'] : null,
			'publication_date'     => isset( $acf_fields['publication_date'] ) ? $acf_fields['publication_date'] : null,
			'key_findings'         => isset( $acf_fields['key_findings'] ) ? $acf_fields['key_findings'] : array(),
			'data_visualization_id' => isset( $acf_fields['data_visualization_id'] ) ? $acf_fields['data_visualization_id'] : null,
		);

		$items[] = mw_format_rest_post( $post, $index_data );
	}

	$response = new WP_REST_Response( $items, 200 );
	return mw_add_pagination_headers( $response, $query, $request );
}
