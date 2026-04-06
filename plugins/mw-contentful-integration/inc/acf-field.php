<?php
/**
 * Custom ACF field type for Contentful images.
 *
 * Registers a "contentful_image" field type that allows editors to browse
 * and select images from Contentful within the ACF field interface.
 *
 * @package MW_Contentful_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Contentful Image ACF field type.
 */
function mw_contentful_register_acf_field_type() {
	if ( ! function_exists( 'acf_register_field_type' ) ) {
		return;
	}

	acf_register_field_type( 'MWContentfulImageField' );
}
add_action( 'acf/include_field_types', 'mw_contentful_register_acf_field_type' );

/**
 * Class MWContentfulImageField
 *
 * ACF field type that stores a Contentful asset reference as JSON.
 */
class MWContentfulImageField extends acf_field {

	/**
	 * Field type name.
	 *
	 * @var string
	 */
	public $name = 'contentful_image';

	/**
	 * Field label.
	 *
	 * @var string
	 */
	public $label = 'Contentful Image';

	/**
	 * Field category.
	 *
	 * @var string
	 */
	public $category = 'content';

	/**
	 * Default field values.
	 *
	 * @var array
	 */
	public $defaults = array(
		'default_width'  => 800,
		'default_height' => 600,
	);

	/**
	 * Initialize the field type.
	 */
	public function initialize() {
		$this->name     = 'contentful_image';
		$this->label    = __( 'Contentful Image', 'mw-contentful-integration' );
		$this->category = 'content';
		$this->defaults = array(
			'default_width'  => 800,
			'default_height' => 600,
		);
	}

	/**
	 * Render field settings in the ACF field group editor.
	 *
	 * @param array $field The field settings array.
	 */
	public function render_field_settings( $field ) {
		acf_render_field_setting( $field, array(
			'label'        => __( 'Default Width', 'mw-contentful-integration' ),
			'instructions' => __( 'Default image width in pixels.', 'mw-contentful-integration' ),
			'type'         => 'number',
			'name'         => 'default_width',
		) );

		acf_render_field_setting( $field, array(
			'label'        => __( 'Default Height', 'mw-contentful-integration' ),
			'instructions' => __( 'Default image height in pixels.', 'mw-contentful-integration' ),
			'type'         => 'number',
			'name'         => 'default_height',
		) );
	}

	/**
	 * Render the field input in the post editor.
	 *
	 * @param array $field The field settings and value.
	 */
	public function render_field( $field ) {
		$value    = $field['value'];
		$data     = array();
		$has_image = false;

		if ( ! empty( $value ) ) {
			if ( is_string( $value ) ) {
				$data = json_decode( $value, true );
			} elseif ( is_array( $value ) ) {
				$data = $value;
			}

			if ( ! empty( $data['asset_id'] ) ) {
				$has_image = true;
			}
		}

		$asset_id     = $has_image ? esc_attr( $data['asset_id'] ) : '';
		$filename     = $has_image && isset( $data['filename'] ) ? esc_attr( $data['filename'] ) : '';
		$content_type = $has_image && isset( $data['content_type'] ) ? esc_attr( $data['content_type'] ) : '';
		$width        = $has_image && isset( $data['width'] ) ? (int) $data['width'] : 0;
		$height       = $has_image && isset( $data['height'] ) ? (int) $data['height'] : 0;
		$title        = $has_image && isset( $data['title'] ) ? esc_attr( $data['title'] ) : '';
		$url          = $has_image && isset( $data['url'] ) ? esc_url( $data['url'] ) : '';

		// Build thumbnail URL for preview.
		$thumb_url = '';
		if ( $has_image && ! empty( $url ) ) {
			$thumb_src = $url;
			if ( strpos( $thumb_src, '//' ) === 0 ) {
				$thumb_src = 'https:' . $thumb_src;
			}
			$thumb_url = add_query_arg( array( 'w' => 300, 'h' => 200, 'fit' => 'fill', 'fm' => 'webp', 'q' => 70 ), $thumb_src );
		}

		$json_value = $has_image ? wp_json_encode( $data ) : '';
		?>
		<div class="mw-contentful-acf-field" data-field-key="<?php echo esc_attr( $field['key'] ); ?>">
			<input
				type="hidden"
				name="<?php echo esc_attr( $field['name'] ); ?>"
				value="<?php echo esc_attr( $json_value ); ?>"
				class="mw-contentful-acf-value"
			/>

			<div class="mw-contentful-acf-preview" style="<?php echo $has_image ? '' : 'display:none;'; ?>">
				<div class="mw-contentful-acf-thumb">
					<?php if ( $thumb_url ) : ?>
						<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
					<?php endif; ?>
				</div>
				<div class="mw-contentful-acf-meta">
					<strong class="mw-contentful-acf-title"><?php echo esc_html( $title ); ?></strong>
					<span class="mw-contentful-acf-filename"><?php echo esc_html( $filename ); ?></span>
					<span class="mw-contentful-acf-dimensions">
						<?php
						if ( $width && $height ) {
							echo esc_html( $width . ' x ' . $height . 'px' );
						}
						?>
					</span>
					<span class="mw-contentful-acf-id">
						<?php if ( $asset_id ) : ?>
							ID: <?php echo esc_html( $asset_id ); ?>
						<?php endif; ?>
					</span>
				</div>
			</div>

			<div class="mw-contentful-acf-actions">
				<button type="button" class="button mw-contentful-browse-btn">
					<?php echo $has_image ? esc_html__( 'Replace Image', 'mw-contentful-integration' ) : esc_html__( 'Browse Contentful', 'mw-contentful-integration' ); ?>
				</button>
				<button type="button" class="button mw-contentful-remove-btn" style="<?php echo $has_image ? '' : 'display:none;'; ?>">
					<?php esc_html_e( 'Remove', 'mw-contentful-integration' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Validate the field value before saving.
	 *
	 * @param bool   $valid   Whether the value is valid.
	 * @param mixed  $value   The field value.
	 * @param array  $field   The field settings.
	 * @param string $input   The input name.
	 * @return bool|string True if valid, error message string if not.
	 */
	public function validate_value( $valid, $value, $field, $input ) {
		if ( ! $valid ) {
			return $valid;
		}

		if ( $field['required'] && empty( $value ) ) {
			return __( 'Please select a Contentful image.', 'mw-contentful-integration' );
		}

		if ( ! empty( $value ) ) {
			$data = is_string( $value ) ? json_decode( $value, true ) : $value;
			if ( ! is_array( $data ) || empty( $data['asset_id'] ) ) {
				return __( 'Invalid Contentful image data.', 'mw-contentful-integration' );
			}
		}

		return $valid;
	}

	/**
	 * Sanitize the field value before saving to the database.
	 *
	 * @param mixed $value   The raw value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field settings.
	 * @return string Sanitized JSON string.
	 */
	public function update_value( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			return '';
		}

		$data = is_string( $value ) ? json_decode( $value, true ) : $value;

		if ( ! is_array( $data ) || empty( $data['asset_id'] ) ) {
			return '';
		}

		$sanitized = array(
			'asset_id'     => sanitize_text_field( $data['asset_id'] ),
			'title'        => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
			'filename'     => isset( $data['filename'] ) ? sanitize_file_name( $data['filename'] ) : '',
			'content_type' => isset( $data['content_type'] ) ? sanitize_mime_type( $data['content_type'] ) : '',
			'url'          => isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '',
			'width'        => isset( $data['width'] ) ? absint( $data['width'] ) : 0,
			'height'       => isset( $data['height'] ) ? absint( $data['height'] ) : 0,
		);

		return wp_json_encode( $sanitized );
	}

	/**
	 * Format the field value when loaded from the database.
	 *
	 * @param mixed $value   The raw value from the database.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field settings.
	 * @return array|false Parsed asset data array or false if empty.
	 */
	public function format_value( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			return false;
		}

		$data = is_string( $value ) ? json_decode( $value, true ) : $value;

		if ( ! is_array( $data ) || empty( $data['asset_id'] ) ) {
			return false;
		}

		return $data;
	}
}

/**
 * Get a Contentful image <img> tag from an ACF field.
 *
 * @param string   $field_name The ACF field name.
 * @param int|bool $post_id    The post ID. Defaults to current post.
 * @param array    $args {
 *     Optional. Image output arguments.
 *
 *     @type int    $width   Desired width. Default 800.
 *     @type int    $height  Desired height. Default 0 (auto).
 *     @type string $format  Image format (webp, jpg, png). Default 'webp'.
 *     @type int    $quality Image quality 1-100. Default 80.
 *     @type string $alt     Alt text override. Default from Contentful metadata.
 *     @type string $class   CSS class(es) for the img tag.
 *     @type array  $widths  Responsive srcset widths. Default [400, 800, 1200, 1600].
 *     @type string $sizes   Sizes attribute. Default '(max-width: 800px) 100vw, 800px'.
 *     @type bool   $lazy    Whether to add loading="lazy". Default true.
 * }
 * @return string HTML <img> tag or empty string if no image.
 */
function mw_get_contentful_image( $field_name, $post_id = false, $args = array() ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$data = get_field( $field_name, $post_id );

	if ( empty( $data ) || ! is_array( $data ) || empty( $data['asset_id'] ) ) {
		return '';
	}

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

	$alt = ! empty( $args['alt'] ) ? $args['alt'] : ( ! empty( $data['title'] ) ? $data['title'] : '' );
	$url = ! empty( $data['url'] ) ? $data['url'] : '';

	if ( empty( $url ) ) {
		// Fall back to API lookup.
		$client = mw_contentful_client();
		$asset  = $client->get_asset( $data['asset_id'] );
		if ( is_wp_error( $asset ) || empty( $asset['url'] ) ) {
			return '';
		}
		$url = $asset['url'];
	}

	// Build primary src.
	$src_params = array(
		'fm'  => $args['format'],
		'q'   => $args['quality'],
		'fit' => 'fill',
	);

	if ( $args['width'] > 0 ) {
		$src_params['w'] = $args['width'];
	}

	if ( $args['height'] > 0 ) {
		$src_params['h'] = $args['height'];
	}

	$src = MWContentfulClient::build_url_from_file( $url, $src_params );

	// Build srcset.
	$srcset_parts = array();
	foreach ( $args['widths'] as $w ) {
		$srcset_params = array(
			'w'   => $w,
			'fm'  => $args['format'],
			'q'   => $args['quality'],
			'fit' => 'fill',
		);
		$srcset_url     = MWContentfulClient::build_url_from_file( $url, $srcset_params );
		$srcset_parts[] = esc_url( $srcset_url ) . ' ' . $w . 'w';
	}

	// Build fallback srcset in original format for browsers not supporting webp.
	$fallback_format = 'jpg';
	if ( ! empty( $data['content_type'] ) ) {
		if ( strpos( $data['content_type'], 'png' ) !== false ) {
			$fallback_format = 'png';
		}
	}

	// Build attributes.
	$attrs = array(
		'src'    => esc_url( $src ),
		'srcset' => implode( ', ', $srcset_parts ),
		'sizes'  => esc_attr( $args['sizes'] ),
		'alt'    => esc_attr( $alt ),
	);

	if ( ! empty( $args['class'] ) ) {
		$attrs['class'] = esc_attr( $args['class'] );
	}

	if ( $args['lazy'] ) {
		$attrs['loading'] = 'lazy';
		$attrs['decoding'] = 'async';
	}

	if ( $args['width'] > 0 ) {
		$attrs['width'] = (int) $args['width'];
	}

	if ( $args['height'] > 0 ) {
		$attrs['height'] = (int) $args['height'];
	}

	$attr_string = '';
	foreach ( $attrs as $key => $val ) {
		$attr_string .= ' ' . $key . '="' . $val . '"';
	}

	return '<img' . $attr_string . ' />';
}
