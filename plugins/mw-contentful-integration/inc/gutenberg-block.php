<?php
/**
 * Gutenberg block for Contentful images.
 *
 * Registers the "mw/contentful-image" block with server-side rendering.
 * In the editor: shows asset ID input and a preview.
 * On the frontend: renders a responsive Contentful image.
 *
 * @package MW_Contentful_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Contentful Image Gutenberg block.
 */
function mw_contentful_register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// Register the editor script.
	wp_register_script(
		'mw-contentful-block-editor',
		MW_CONTENTFUL_URL . 'assets/js/block-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ),
		MW_CONTENTFUL_VERSION,
		true
	);

	wp_localize_script( 'mw-contentful-block-editor', 'mwContentfulBlock', array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'mw_contentful_nonce' ),
		'imgBase'  => MW_CONTENTFUL_IMAGES_BASE,
	) );

	register_block_type( 'mw/contentful-image', array(
		'editor_script'   => 'mw-contentful-block-editor',
		'attributes'      => array(
			'assetId'   => array(
				'type'    => 'string',
				'default' => '',
			),
			'width'     => array(
				'type'    => 'number',
				'default' => 800,
			),
			'height'    => array(
				'type'    => 'number',
				'default' => 0,
			),
			'alt'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'caption'   => array(
				'type'    => 'string',
				'default' => '',
			),
			'className' => array(
				'type'    => 'string',
				'default' => '',
			),
		),
		'render_callback' => 'mw_contentful_render_block',
	) );
}
add_action( 'init', 'mw_contentful_register_block' );

/**
 * Server-side render callback for the Contentful Image block.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered HTML.
 */
function mw_contentful_render_block( $attributes ) {
	$asset_id  = ! empty( $attributes['assetId'] ) ? sanitize_text_field( $attributes['assetId'] ) : '';
	$width     = ! empty( $attributes['width'] ) ? absint( $attributes['width'] ) : 800;
	$height    = ! empty( $attributes['height'] ) ? absint( $attributes['height'] ) : 0;
	$alt       = ! empty( $attributes['alt'] ) ? sanitize_text_field( $attributes['alt'] ) : '';
	$caption   = ! empty( $attributes['caption'] ) ? sanitize_text_field( $attributes['caption'] ) : '';
	$classname = ! empty( $attributes['className'] ) ? sanitize_html_class( $attributes['className'] ) : '';

	if ( empty( $asset_id ) ) {
		return '';
	}

	$args = array(
		'width'   => $width,
		'height'  => $height,
		'format'  => 'webp',
		'quality' => 80,
		'alt'     => $alt,
		'class'   => '',
	);

	$img_html = mw_contentful_img( $asset_id, $args );

	if ( empty( $img_html ) ) {
		return '';
	}

	$figure_class = 'wp-block-mw-contentful-image mw-contentful-figure';
	if ( ! empty( $classname ) ) {
		$figure_class .= ' ' . $classname;
	}

	$output  = '<figure class="' . esc_attr( $figure_class ) . '">';
	$output .= $img_html;

	if ( ! empty( $caption ) ) {
		$output .= '<figcaption class="mw-contentful-caption">' . esc_html( $caption ) . '</figcaption>';
	}

	$output .= '</figure>';

	return $output;
}

/**
 * Enqueue the block editor stylesheet alongside the block script.
 */
function mw_contentful_block_editor_assets() {
	wp_enqueue_style(
		'mw-contentful-block-editor',
		MW_CONTENTFUL_URL . 'assets/css/admin-styles.css',
		array(),
		MW_CONTENTFUL_VERSION
	);
}
add_action( 'enqueue_block_editor_assets', 'mw_contentful_block_editor_assets' );
