<?php
/**
 * Contentful image shortcode.
 *
 * Usage:
 * [contentful_image id="ASSET_ID" width="800" height="600" format="webp" quality="80" alt="Alt text" class="custom-class" caption="Optional caption"]
 *
 * @package MW_Contentful_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the [contentful_image] shortcode.
 */
function mw_contentful_register_shortcode() {
	add_shortcode( 'contentful_image', 'mw_contentful_render_shortcode' );
}
add_action( 'init', 'mw_contentful_register_shortcode' );

/**
 * Render the [contentful_image] shortcode.
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content (unused).
 * @return string HTML output.
 */
function mw_contentful_render_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'id'      => '',
			'width'   => 800,
			'height'  => 0,
			'format'  => 'webp',
			'quality' => 80,
			'alt'     => '',
			'class'   => '',
			'caption' => '',
			'sizes'   => '(max-width: 800px) 100vw, 800px',
			'lazy'    => 'true',
		),
		$atts,
		'contentful_image'
	);

	$asset_id = sanitize_text_field( $atts['id'] );

	if ( empty( $asset_id ) ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="mw-contentful-error">' . esc_html__( '[Contentful Image: Missing asset ID]', 'mw-contentful-integration' ) . '</p>';
		}
		return '';
	}

	$client = mw_contentful_client();
	$asset  = $client->get_asset( $asset_id );

	if ( is_wp_error( $asset ) ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<p class="mw-contentful-error">' . esc_html( $asset->get_error_message() ) . '</p>';
		}
		return '';
	}

	if ( empty( $asset['url'] ) ) {
		return '';
	}

	$width   = absint( $atts['width'] );
	$height  = absint( $atts['height'] );
	$format  = sanitize_text_field( $atts['format'] );
	$quality = absint( $atts['quality'] );
	$alt     = ! empty( $atts['alt'] ) ? sanitize_text_field( $atts['alt'] ) : ( ! empty( $asset['title'] ) ? $asset['title'] : '' );
	$class   = ! empty( $atts['class'] ) ? sanitize_html_class( $atts['class'] ) : '';
	$caption = sanitize_text_field( $atts['caption'] );
	$lazy    = 'false' !== $atts['lazy'];
	$sizes   = sanitize_text_field( $atts['sizes'] );

	// Build primary src URL.
	$src_params = array(
		'fm'  => $format,
		'q'   => $quality,
		'fit' => 'fill',
	);

	if ( $width > 0 ) {
		$src_params['w'] = $width;
	}

	if ( $height > 0 ) {
		$src_params['h'] = $height;
	}

	$src = MWContentfulClient::build_url_from_file( $asset['url'], $src_params );

	// Build responsive srcset.
	$srcset_widths = array( 400, 800, 1200, 1600 );
	$srcset_parts  = array();

	foreach ( $srcset_widths as $w ) {
		$srcset_params = array(
			'w'   => $w,
			'fm'  => $format,
			'q'   => $quality,
			'fit' => 'fill',
		);
		$srcset_url     = MWContentfulClient::build_url_from_file( $asset['url'], $srcset_params );
		$srcset_parts[] = esc_url( $srcset_url ) . ' ' . $w . 'w';
	}

	// Build the <img> tag.
	$img_attrs = array(
		'src'    => esc_url( $src ),
		'srcset' => implode( ', ', $srcset_parts ),
		'sizes'  => esc_attr( $sizes ),
		'alt'    => esc_attr( $alt ),
	);

	if ( ! empty( $class ) ) {
		$img_attrs['class'] = esc_attr( $class );
	}

	if ( $lazy ) {
		$img_attrs['loading']  = 'lazy';
		$img_attrs['decoding'] = 'async';
	}

	if ( $width > 0 ) {
		$img_attrs['width'] = $width;
	}

	if ( $height > 0 ) {
		$img_attrs['height'] = $height;
	}

	$attr_string = '';
	foreach ( $img_attrs as $key => $val ) {
		$attr_string .= ' ' . $key . '="' . $val . '"';
	}

	$img_tag = '<img' . $attr_string . ' />';

	// Wrap in <figure> if there is a caption, or always for consistent styling.
	$figure_class = 'mw-contentful-figure';
	if ( ! empty( $class ) ) {
		$figure_class .= ' ' . esc_attr( $class );
	}

	$output = '<figure class="' . esc_attr( $figure_class ) . '">';
	$output .= $img_tag;

	if ( ! empty( $caption ) ) {
		$output .= '<figcaption class="mw-contentful-caption">' . esc_html( $caption ) . '</figcaption>';
	}

	$output .= '</figure>';

	return $output;
}
