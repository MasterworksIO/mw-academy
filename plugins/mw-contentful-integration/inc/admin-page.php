<?php
/**
 * Contentful Media admin page.
 *
 * Adds a "Contentful Media" submenu page under the Academy menu with a grid
 * view of Contentful assets, search, filtering, and preview modal.
 *
 * @package MW_Contentful_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Contentful Media submenu page under the Academy menu.
 */
function mw_contentful_register_admin_page() {
	add_submenu_page(
		'mw-academy',
		__( 'Contentful Media', 'mw-contentful-integration' ),
		__( 'Contentful Media', 'mw-contentful-integration' ),
		'edit_posts',
		'mw-contentful-media',
		'mw_contentful_render_admin_page'
	);
}
add_action( 'admin_menu', 'mw_contentful_register_admin_page' );

/**
 * Render the Contentful Media admin page.
 */
function mw_contentful_render_admin_page() {
	$credentials = mw_contentful_get_credentials();
	$configured  = ! empty( $credentials['space_id'] ) && ! empty( $credentials['access_token'] );
	?>
	<div class="wrap mw-contentful-admin-wrap">
		<h1><?php esc_html_e( 'Contentful Media Browser', 'mw-contentful-integration' ); ?></h1>

		<?php if ( ! $configured ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						/* translators: %s: settings page URL */
						wp_kses(
							__( 'Contentful credentials are not configured. Please set them in the <a href="%s">Academy Settings</a>.', 'mw-contentful-integration' ),
							array( 'a' => array( 'href' => array() ) )
						),
						esc_url( admin_url( 'admin.php?page=mw-academy-settings' ) )
					);
					?>
				</p>
			</div>
		<?php else : ?>

			<!-- Search and Filter Bar -->
			<div class="mw-contentful-toolbar">
				<div class="mw-contentful-search-wrap">
					<input
						type="text"
						id="mw-contentful-search"
						class="mw-contentful-search-input"
						placeholder="<?php esc_attr_e( 'Search assets...', 'mw-contentful-integration' ); ?>"
					/>
					<span class="mw-contentful-search-icon dashicons dashicons-search"></span>
				</div>

				<div class="mw-contentful-filter-wrap">
					<select id="mw-contentful-filter-type" class="mw-contentful-filter-select">
						<option value=""><?php esc_html_e( 'All Image Types', 'mw-contentful-integration' ); ?></option>
						<option value="image/jpeg"><?php esc_html_e( 'JPEG', 'mw-contentful-integration' ); ?></option>
						<option value="image/png"><?php esc_html_e( 'PNG', 'mw-contentful-integration' ); ?></option>
						<option value="image/svg+xml"><?php esc_html_e( 'SVG', 'mw-contentful-integration' ); ?></option>
						<option value="image/webp"><?php esc_html_e( 'WebP', 'mw-contentful-integration' ); ?></option>
						<option value="image/gif"><?php esc_html_e( 'GIF', 'mw-contentful-integration' ); ?></option>
					</select>
				</div>

				<button type="button" id="mw-contentful-refresh" class="button">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Refresh', 'mw-contentful-integration' ); ?>
				</button>
			</div>

			<!-- Loading State -->
			<div id="mw-contentful-loading" class="mw-contentful-loading" style="display:none;">
				<span class="spinner is-active"></span>
				<span><?php esc_html_e( 'Loading assets...', 'mw-contentful-integration' ); ?></span>
			</div>

			<!-- Error State -->
			<div id="mw-contentful-error" class="mw-contentful-error notice notice-error" style="display:none;">
				<p id="mw-contentful-error-message"></p>
			</div>

			<!-- Assets Grid -->
			<div id="mw-contentful-grid" class="mw-contentful-grid"></div>

			<!-- Pagination -->
			<div id="mw-contentful-pagination" class="mw-contentful-pagination" style="display:none;">
				<button type="button" id="mw-contentful-prev" class="button" disabled>
					&laquo; <?php esc_html_e( 'Previous', 'mw-contentful-integration' ); ?>
				</button>
				<span id="mw-contentful-page-info" class="mw-contentful-page-info"></span>
				<button type="button" id="mw-contentful-next" class="button">
					<?php esc_html_e( 'Next', 'mw-contentful-integration' ); ?> &raquo;
				</button>
			</div>

			<!-- Preview Modal -->
			<div id="mw-contentful-modal" class="mw-contentful-modal" style="display:none;">
				<div class="mw-contentful-modal-overlay"></div>
				<div class="mw-contentful-modal-content">
					<button type="button" class="mw-contentful-modal-close">&times;</button>
					<div class="mw-contentful-modal-body">
						<div class="mw-contentful-modal-image">
							<img id="mw-contentful-modal-img" src="" alt="" />
						</div>
						<div class="mw-contentful-modal-details">
							<h2 id="mw-contentful-modal-title"></h2>
							<table class="mw-contentful-modal-meta">
								<tr>
									<th><?php esc_html_e( 'Asset ID', 'mw-contentful-integration' ); ?></th>
									<td>
										<code id="mw-contentful-modal-id"></code>
										<button type="button" class="button button-small mw-contentful-copy-id" data-copy-target="mw-contentful-modal-id">
											<?php esc_html_e( 'Copy', 'mw-contentful-integration' ); ?>
										</button>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Filename', 'mw-contentful-integration' ); ?></th>
									<td id="mw-contentful-modal-filename"></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Type', 'mw-contentful-integration' ); ?></th>
									<td id="mw-contentful-modal-type"></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Dimensions', 'mw-contentful-integration' ); ?></th>
									<td id="mw-contentful-modal-dimensions"></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'File Size', 'mw-contentful-integration' ); ?></th>
									<td id="mw-contentful-modal-size"></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Description', 'mw-contentful-integration' ); ?></th>
									<td id="mw-contentful-modal-description"></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Created', 'mw-contentful-integration' ); ?></th>
									<td id="mw-contentful-modal-created"></td>
								</tr>
							</table>

							<div class="mw-contentful-modal-actions">
								<button type="button" class="button button-primary mw-contentful-modal-select">
									<?php esc_html_e( 'Select Image', 'mw-contentful-integration' ); ?>
								</button>
								<button type="button" class="button mw-contentful-modal-copy-url">
									<?php esc_html_e( 'Copy CDN URL', 'mw-contentful-integration' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Copied Toast -->
			<div id="mw-contentful-toast" class="mw-contentful-toast" style="display:none;">
				<?php esc_html_e( 'Copied to clipboard!', 'mw-contentful-integration' ); ?>
			</div>

		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle AJAX request to fetch Contentful assets.
 */
function mw_contentful_ajax_get_assets() {
	check_ajax_referer( 'mw_contentful_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mw-contentful-integration' ) ), 403 );
	}

	$query         = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
	$limit         = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 20;
	$skip          = isset( $_POST['skip'] ) ? absint( $_POST['skip'] ) : 0;
	$content_type  = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : '';

	$client = mw_contentful_client();
	$result = $client->get_assets( $query, $limit, $skip, 'image' );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	// Filter by specific content type if provided.
	if ( ! empty( $content_type ) && ! empty( $result['items'] ) ) {
		$result['items'] = array_values( array_filter( $result['items'], function( $item ) use ( $content_type ) {
			return isset( $item['content_type'] ) && $item['content_type'] === $content_type;
		} ) );
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_mw_contentful_get_assets', 'mw_contentful_ajax_get_assets' );

/**
 * Handle AJAX request to get a single asset's details.
 */
function mw_contentful_ajax_get_asset() {
	check_ajax_referer( 'mw_contentful_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mw-contentful-integration' ) ), 403 );
	}

	$asset_id = isset( $_POST['asset_id'] ) ? sanitize_text_field( wp_unslash( $_POST['asset_id'] ) ) : '';

	if ( empty( $asset_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Asset ID is required.', 'mw-contentful-integration' ) ) );
	}

	$client = mw_contentful_client();
	$asset  = $client->get_asset( $asset_id );

	if ( is_wp_error( $asset ) ) {
		wp_send_json_error( array( 'message' => $asset->get_error_message() ) );
	}

	wp_send_json_success( $asset );
}
add_action( 'wp_ajax_mw_contentful_get_asset', 'mw_contentful_ajax_get_asset' );

/**
 * Handle AJAX request to clear the Contentful cache.
 */
function mw_contentful_ajax_clear_cache() {
	check_ajax_referer( 'mw_contentful_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mw-contentful-integration' ) ), 403 );
	}

	$client = mw_contentful_client();
	$client->clear_cache();

	wp_send_json_success( array( 'message' => __( 'Cache cleared.', 'mw-contentful-integration' ) ) );
}
add_action( 'wp_ajax_mw_contentful_clear_cache', 'mw_contentful_ajax_clear_cache' );
