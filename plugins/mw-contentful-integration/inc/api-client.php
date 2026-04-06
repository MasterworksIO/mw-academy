<?php
/**
 * Contentful API client.
 *
 * @package MW_Contentful_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MWContentfulClient
 *
 * Handles all communication with the Contentful Content Delivery API.
 * Caches responses in WordPress transients to reduce API calls.
 */
class MWContentfulClient {

	/**
	 * Contentful Space ID.
	 *
	 * @var string
	 */
	private $space_id;

	/**
	 * Contentful Content Delivery API access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * Transient cache TTL in seconds (1 hour).
	 *
	 * @var int
	 */
	private $cache_ttl = 3600;

	/**
	 * Constructor.
	 *
	 * @param string $space_id     Contentful Space ID. Falls back to plugin settings.
	 * @param string $access_token Content Delivery API access token. Falls back to plugin settings.
	 */
	public function __construct( $space_id = '', $access_token = '' ) {
		if ( empty( $space_id ) || empty( $access_token ) ) {
			$credentials      = mw_contentful_get_credentials();
			$this->space_id     = ! empty( $space_id ) ? $space_id : $credentials['space_id'];
			$this->access_token = ! empty( $access_token ) ? $access_token : $credentials['access_token'];
		} else {
			$this->space_id     = $space_id;
			$this->access_token = $access_token;
		}
	}

	/**
	 * Check if the client has valid credentials configured.
	 *
	 * @return bool True if space_id and access_token are set.
	 */
	public function is_configured() {
		return ! empty( $this->space_id ) && ! empty( $this->access_token );
	}

	/**
	 * Search and retrieve assets from Contentful.
	 *
	 * @param string $query  Search query string to match against asset titles.
	 * @param int    $limit  Number of assets to return (max 100).
	 * @param int    $skip   Number of assets to skip for pagination.
	 * @param string $mimetype_group Optional MIME type group filter (e.g., 'image').
	 * @return array|WP_Error Array of asset data or WP_Error on failure.
	 */
	public function get_assets( $query = '', $limit = 20, $skip = 0, $mimetype_group = '' ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'mw_contentful_not_configured', __( 'Contentful credentials are not configured.', 'mw-contentful-integration' ) );
		}

		$limit = min( absint( $limit ), 100 );
		$skip  = absint( $skip );

		$params = array(
			'limit' => $limit,
			'skip'  => $skip,
			'order' => '-sys.createdAt',
		);

		if ( ! empty( $query ) ) {
			$params['query'] = sanitize_text_field( $query );
		}

		if ( ! empty( $mimetype_group ) ) {
			$params['mimetype_group'] = sanitize_text_field( $mimetype_group );
		}

		$cache_key = 'mw_ctfl_assets_' . md5( wp_json_encode( $params ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request( '/assets', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$result = array(
			'items' => array(),
			'total' => isset( $response['total'] ) ? (int) $response['total'] : 0,
			'skip'  => isset( $response['skip'] ) ? (int) $response['skip'] : $skip,
			'limit' => isset( $response['limit'] ) ? (int) $response['limit'] : $limit,
		);

		if ( ! empty( $response['items'] ) && is_array( $response['items'] ) ) {
			foreach ( $response['items'] as $item ) {
				$result['items'][] = $this->normalize_asset( $item );
			}
		}

		set_transient( $cache_key, $result, $this->cache_ttl );

		return $result;
	}

	/**
	 * Get a single asset by ID.
	 *
	 * @param string $asset_id The Contentful asset ID.
	 * @return array|WP_Error Asset data array or WP_Error on failure.
	 */
	public function get_asset( $asset_id ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'mw_contentful_not_configured', __( 'Contentful credentials are not configured.', 'mw-contentful-integration' ) );
		}

		$asset_id = sanitize_text_field( $asset_id );

		if ( empty( $asset_id ) ) {
			return new WP_Error( 'mw_contentful_invalid_id', __( 'Invalid asset ID.', 'mw-contentful-integration' ) );
		}

		$cache_key = 'mw_ctfl_asset_' . $asset_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request( '/assets/' . $asset_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$asset = $this->normalize_asset( $response );

		set_transient( $cache_key, $asset, $this->cache_ttl );

		return $asset;
	}

	/**
	 * Build a Contentful CDN URL for an asset with optional image transforms.
	 *
	 * @param string $asset_id The Contentful asset ID.
	 * @param array  $params   Optional image transformation parameters.
	 *                         Supports: w (width), h (height), f (focus), fit, fm (format), q (quality), fl (flags).
	 * @return string|WP_Error The CDN URL string or WP_Error on failure.
	 */
	public function get_asset_url( $asset_id, $params = array() ) {
		$asset = $this->get_asset( $asset_id );

		if ( is_wp_error( $asset ) ) {
			return $asset;
		}

		if ( empty( $asset['url'] ) ) {
			return new WP_Error( 'mw_contentful_no_url', __( 'Asset has no file URL.', 'mw-contentful-integration' ) );
		}

		$url = 'https:' . $asset['url'];

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		return $url;
	}

	/**
	 * Build an optimized image URL with specific dimensions, format, and quality.
	 *
	 * @param string $asset_id The Contentful asset ID.
	 * @param int    $width    Desired image width in pixels.
	 * @param int    $height   Desired image height in pixels. Use 0 for auto.
	 * @param string $format   Image format (webp, jpg, png, gif, avif). Default 'webp'.
	 * @param int    $quality  Image quality 1-100. Default 80.
	 * @return string|WP_Error The optimized CDN URL or WP_Error on failure.
	 */
	public function build_image_url( $asset_id, $width = 0, $height = 0, $format = 'webp', $quality = 80 ) {
		$params = array();

		if ( $width > 0 ) {
			$params['w'] = absint( $width );
		}

		if ( $height > 0 ) {
			$params['h'] = absint( $height );
		}

		if ( ! empty( $format ) ) {
			$allowed_formats = array( 'webp', 'jpg', 'png', 'gif', 'avif' );
			if ( in_array( $format, $allowed_formats, true ) ) {
				$params['fm'] = $format;
			}
		}

		$quality = absint( $quality );
		if ( $quality > 0 && $quality <= 100 ) {
			$params['q'] = $quality;
		}

		$params['fit'] = 'fill';

		return $this->get_asset_url( $asset_id, $params );
	}

	/**
	 * Build an image URL directly from a known file URL (bypasses API call).
	 *
	 * @param string $file_url The raw Contentful file URL (from stored metadata).
	 * @param array  $params   Image transformation parameters.
	 * @return string The CDN URL with transforms applied.
	 */
	public static function build_url_from_file( $file_url, $params = array() ) {
		$url = $file_url;

		// Ensure protocol.
		if ( strpos( $url, '//' ) === 0 ) {
			$url = 'https:' . $url;
		}

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		return $url;
	}

	/**
	 * Clear all Contentful-related transient caches.
	 *
	 * @return void
	 */
	public function clear_cache() {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mw_ctfl_%' OR option_name LIKE '_transient_timeout_mw_ctfl_%'"
		);
	}

	/**
	 * Make an HTTP request to the Contentful API.
	 *
	 * @param string $endpoint API endpoint path (e.g., '/assets').
	 * @param array  $params   Query parameters.
	 * @return array|WP_Error Decoded response body or WP_Error.
	 */
	private function request( $endpoint, $params = array() ) {
		$url = MW_CONTENTFUL_CDN_BASE . '/spaces/' . $this->space_id . '/environments/master' . $endpoint;

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$response = wp_remote_get( $url, array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
				'Content-Type'  => 'application/json',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'mw_contentful_request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Contentful API request failed: %s', 'mw-contentful-integration' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $data['message'] ) ? $data['message'] : __( 'Unknown API error.', 'mw-contentful-integration' );
			return new WP_Error(
				'mw_contentful_api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'Contentful API error (%1$d): %2$s', 'mw-contentful-integration' ),
					$code,
					$message
				),
				array( 'status' => $code )
			);
		}

		if ( null === $data ) {
			return new WP_Error( 'mw_contentful_invalid_json', __( 'Invalid JSON response from Contentful.', 'mw-contentful-integration' ) );
		}

		return $data;
	}

	/**
	 * Normalize a Contentful asset response into a consistent array structure.
	 *
	 * @param array $item Raw asset item from the Contentful API.
	 * @return array Normalized asset data.
	 */
	private function normalize_asset( $item ) {
		$asset = array(
			'id'           => '',
			'title'        => '',
			'description'  => '',
			'filename'     => '',
			'content_type' => '',
			'url'          => '',
			'width'        => 0,
			'height'       => 0,
			'size'         => 0,
			'created_at'   => '',
			'updated_at'   => '',
		);

		if ( isset( $item['sys']['id'] ) ) {
			$asset['id'] = $item['sys']['id'];
		}

		if ( isset( $item['sys']['createdAt'] ) ) {
			$asset['created_at'] = $item['sys']['createdAt'];
		}

		if ( isset( $item['sys']['updatedAt'] ) ) {
			$asset['updated_at'] = $item['sys']['updatedAt'];
		}

		if ( isset( $item['fields'] ) ) {
			$fields = $item['fields'];

			if ( isset( $fields['title'] ) ) {
				$asset['title'] = is_array( $fields['title'] ) ? reset( $fields['title'] ) : $fields['title'];
			}

			if ( isset( $fields['description'] ) ) {
				$asset['description'] = is_array( $fields['description'] ) ? reset( $fields['description'] ) : $fields['description'];
			}

			if ( isset( $fields['file'] ) ) {
				$file = is_array( $fields['file'] ) && ! isset( $fields['file']['url'] ) ? reset( $fields['file'] ) : $fields['file'];

				if ( isset( $file['url'] ) ) {
					$asset['url'] = $file['url'];
				}

				if ( isset( $file['fileName'] ) ) {
					$asset['filename'] = $file['fileName'];
				}

				if ( isset( $file['contentType'] ) ) {
					$asset['content_type'] = $file['contentType'];
				}

				if ( isset( $file['details']['image']['width'] ) ) {
					$asset['width'] = (int) $file['details']['image']['width'];
				}

				if ( isset( $file['details']['image']['height'] ) ) {
					$asset['height'] = (int) $file['details']['image']['height'];
				}

				if ( isset( $file['details']['size'] ) ) {
					$asset['size'] = (int) $file['details']['size'];
				}
			}
		}

		return $asset;
	}
}

/**
 * Get a shared instance of the Contentful client.
 *
 * @return MWContentfulClient
 */
function mw_contentful_client() {
	static $client = null;

	if ( null === $client ) {
		$client = new MWContentfulClient();
	}

	return $client;
}
