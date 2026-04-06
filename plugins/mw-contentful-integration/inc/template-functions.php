<?php
/**
 * Template functions for theme developers.
 *
 * Provides helper functions for rendering Contentful images in theme templates.
 *
 * @package MW_Contentful_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get an <img> HTML tag for a Contentful asset.
 *
 * @param string $asset_id The Contentful asset ID.
 * @param array  $args {
 *     Optional. Image output arguments.
 *
 *     @type int    $width   Desired width in pixels. Default 800.
 *     @type int    $height  Desired height in pixels. Default 0 (auto).
 *     @type string $format  Image format (webp, jpg, png, gif, avif). Default 'webp'.
 *     @type int    $quality Image quality 1-100. Default 80.
 *     @type string $alt     Alt text. Default from Contentful metadata.
 *     @type string $class   CSS class(es). Default empty.
 *     @type array  $widths  Responsive srcset widths. Default [400, 800, 1200, 1600].
 *     @type string $sizes   Sizes attribute. Default '(max-width: 800px) 100vw, 800px'.
 *     @type bool   $lazy    Whether to add loading="lazy". Default true.
 * }
 * @return string HTML <img> tag or empty string on failure.
 */
function mw_contentful_img( $asset_id, $args = array() ) {
	if ( empty( $asset_id ) ) {
		return '';
	}

	$asset_id = sanitize_text_field( $asset_id );

	$defaults = array(
		'width'   => 800,
		'height'  => 0,
		'format'  => 'webp',
		'quality' => 80,
		'alt'     => '',
		'class'   => '',
		'widths'  => array( 400, 800, 1200, 1600 ),
		'sizes'   => '(max-width: 800px) 100vw, 800px',
		'lazy'    => true,
	);

	$args = wp_parse_args( $args, $defaults );

	$client = mw_contentful_client();
	$asset  = $client->get_asset( $asset_id );

	if ( is_wp_error( $asset ) || empty( $asset['url'] ) ) {
		return '';
	}

	$alt = ! empty( $args['alt'] ) ? $args['alt'] : ( ! empty( $asset['title'] ) ? $asset['title'] : '' );

	// Build primary src URL.
	$src_params = array(
		'fm'  => $args['format'],
		'q'   => $args['quality'],
		'fit' => 'fill',
	);

	if ( $args['width'] > 0 ) {
		$src_params['w'] = absint( $args['width'] );
	}

	if ( $args['height'] > 0 ) {
		$src_params['h'] = absint( $args['height'] );
	}

	$src = MWContentfulClient::build_url_from_file( $asset['url'], $src_params );

	// Build srcset.
	$srcset = mw_contentful_srcset( $asset_id, $args['widths'], $args['format'], $args['quality'] );

	// Build attributes array.
	$attrs = array(
		'src'    => esc_url( $src ),
		'alt'    => esc_attr( $alt ),
	);

	if ( ! empty( $srcset ) ) {
		$attrs['srcset'] = $srcset;
		$attrs['sizes']  = esc_attr( $args['sizes'] );
	}

	if ( ! empty( $args['class'] ) ) {
		$attrs['class'] = esc_attr( $args['class'] );
	}

	if ( $args['lazy'] ) {
		$attrs['loading']  = 'lazy';
		$attrs['decoding'] = 'async';
	}

	if ( $args['width'] > 0 ) {
		$attrs['width'] = absint( $args['width'] );
	}

	if ( $args['height'] > 0 ) {
		$attrs['height'] = absint( $args['height'] );
	} elseif ( ! empty( $asset['width'] ) && ! empty( $asset['height'] ) && $args['width'] > 0 ) {
		// Calculate proportional height from original aspect ratio.
		$ratio = $asset['height'] / $asset['width'];
		$attrs['height'] = round( $args['width'] * $ratio );
	}

	$attr_string = '';
	foreach ( $attrs as $key => $val ) {
		$attr_string .= ' ' . $key . '="' . $val . '"';
	}

	return '<img' . $attr_string . ' />';
}

/**
 * Get the Contentful CDN URL for an asset with optional transforms.
 *
 * @param string $asset_id The Contentful asset ID.
 * @param int    $width    Desired width in pixels. Default 0 (original).
 * @param int    $height   Desired height in pixels. Default 0 (original).
 * @param string $format   Image format. Default 'webp'.
 * @param int    $quality  Image quality 1-100. Default 80.
 * @return string The CDN URL or empty string on failure.
 */
function mw_contentful_url( $asset_id, $width = 0, $height = 0, $format = 'webp', $quality = 80 ) {
	if ( empty( $asset_id ) ) {
		return '';
	}

	$client = mw_contentful_client();
	$url    = $client->build_image_url(
		sanitize_text_field( $asset_id ),
		absint( $width ),
		absint( $height ),
		sanitize_text_field( $format ),
		absint( $quality )
	);

	if ( is_wp_error( $url ) ) {
		return '';
	}

	return esc_url( $url );
}

/**
 * Get a srcset string for a Contentful asset at multiple widths.
 *
 * @param string $asset_id The Contentful asset ID.
 * @param array  $widths   Array of widths in pixels. Default [400, 800, 1200, 1600].
 * @param string $format   Image format. Default 'webp'.
 * @param int    $quality  Image quality 1-100. Default 80.
 * @return string The srcset attribute value or empty string on failure.
 */
function mw_contentful_srcset( $asset_id, $widths = array( 400, 800, 1200, 1600 ), $format = 'webp', $quality = 80 ) {
	if ( empty( $asset_id ) ) {
		return '';
	}

	$client = mw_contentful_client();
	$asset  = $client->get_asset( sanitize_text_field( $asset_id ) );

	if ( is_wp_error( $asset ) || empty( $asset['url'] ) ) {
		return '';
	}

	$srcset_parts = array();

	foreach ( $widths as $w ) {
		$w = absint( $w );
		if ( $w <= 0 ) {
			continue;
		}

		$params = array(
			'w'   => $w,
			'fm'  => sanitize_text_field( $format ),
			'q'   => absint( $quality ),
			'fit' => 'fill',
		);

		$url = MWContentfulClient::build_url_from_file( $asset['url'], $params );
		$srcset_parts[] = esc_url( $url ) . ' ' . $w . 'w';
	}

	return implode( ', ', $srcset_parts );
}

/**
 * Get a CSS background-image style string for a Contentful asset.
 *
 * @param string $asset_id The Contentful asset ID.
 * @param int    $width    Desired width in pixels. Default 1600.
 * @param int    $height   Desired height in pixels. Default 0 (auto).
 * @param string $format   Image format. Default 'webp'.
 * @param int    $quality  Image quality 1-100. Default 80.
 * @return string CSS style string like "background-image: url('...')" or empty string.
 */
function mw_contentful_background( $asset_id, $width = 1600, $height = 0, $format = 'webp', $quality = 80 ) {
	$url = mw_contentful_url( $asset_id, $width, $height, $format, $quality );

	if ( empty( $url ) ) {
		return '';
	}

	return "background-image: url('" . esc_url( $url ) . "')";
}

/**
 * Get just the raw file URL for a Contentful asset (no transforms).
 *
 * @param string $asset_id The Contentful asset ID.
 * @return string The raw CDN URL or empty string.
 */
function mw_contentful_raw_url( $asset_id ) {
	if ( empty( $asset_id ) ) {
		return '';
	}

	$client = mw_contentful_client();
	$asset  = $client->get_asset( sanitize_text_field( $asset_id ) );

	if ( is_wp_error( $asset ) || empty( $asset['url'] ) ) {
		return '';
	}

	$url = $asset['url'];
	if ( strpos( $url, '//' ) === 0 ) {
		$url = 'https:' . $url;
	}

	return esc_url( $url );
}
