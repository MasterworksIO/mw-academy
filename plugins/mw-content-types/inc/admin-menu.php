<?php
/**
 * Admin menu configuration for Masterworks Academy.
 *
 * @package MW_Content_Types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the top-level Academy admin menu.
 */
function mw_register_academy_admin_menu() {
	add_menu_page(
		'Masterworks Academy',
		'Academy',
		'edit_posts',
		'mw-academy',
		'mw_academy_dashboard_page',
		'dashicons-welcome-learn-more',
		26
	);

	add_submenu_page(
		'mw-academy',
		'Academy Dashboard',
		'Dashboard',
		'edit_posts',
		'mw-academy',
		'mw_academy_dashboard_page'
	);

	add_submenu_page(
		'mw-academy',
		'Academy Settings',
		'Settings',
		'manage_options',
		'mw-academy-settings',
		'mw_academy_settings_page'
	);
}
add_action( 'admin_menu', 'mw_register_academy_admin_menu' );

/**
 * Register plugin settings.
 */
function mw_register_academy_settings() {
	register_setting( 'mw_academy_settings', 'mw_academy_options', array(
		'type'              => 'array',
		'sanitize_callback' => 'mw_sanitize_academy_options',
		'default'           => array(
			'contentful_space_id'      => '',
			'contentful_access_token'  => '',
			'contentful_default_image' => '',
			'newsletter_api_key'       => '',
			'newsletter_list_id'       => '',
			'analytics_ga_id'          => '',
			'analytics_gtm_id'         => '',
		),
	) );

	add_settings_section(
		'mw_contentful_section',
		'Contentful Integration',
		function () {
			echo '<p>Configure the connection to Contentful for hero images and media assets.</p>';
		},
		'mw-academy-settings'
	);

	add_settings_field(
		'contentful_space_id',
		'Space ID',
		'mw_render_text_field',
		'mw-academy-settings',
		'mw_contentful_section',
		array( 'key' => 'contentful_space_id', 'description' => 'Your Contentful Space ID.' )
	);

	add_settings_field(
		'contentful_access_token',
		'Access Token',
		'mw_render_text_field',
		'mw-academy-settings',
		'mw_contentful_section',
		array( 'key' => 'contentful_access_token', 'type' => 'password', 'description' => 'Content Delivery API access token.' )
	);

	add_settings_field(
		'contentful_default_image',
		'Default Featured Image ID',
		'mw_render_text_field',
		'mw-academy-settings',
		'mw_contentful_section',
		array( 'key' => 'contentful_default_image', 'description' => 'Contentful asset ID for the default featured image.' )
	);

	add_settings_section(
		'mw_newsletter_section',
		'Newsletter Integration',
		function () {
			echo '<p>Settings for email newsletter API integration.</p>';
		},
		'mw-academy-settings'
	);

	add_settings_field(
		'newsletter_api_key',
		'Newsletter API Key',
		'mw_render_text_field',
		'mw-academy-settings',
		'mw_newsletter_section',
		array( 'key' => 'newsletter_api_key', 'type' => 'password', 'description' => 'API key for the newsletter provider.' )
	);

	add_settings_field(
		'newsletter_list_id',
		'Newsletter List ID',
		'mw_render_text_field',
		'mw-academy-settings',
		'mw_newsletter_section',
		array( 'key' => 'newsletter_list_id', 'description' => 'Default mailing list identifier.' )
	);

	add_settings_section(
		'mw_analytics_section',
		'Analytics Tracking',
		function () {
			echo '<p>Google Analytics and Tag Manager configuration.</p>';
		},
		'mw-academy-settings'
	);

	add_settings_field(
		'analytics_ga_id',
		'Google Analytics ID',
		'mw_render_text_field',
		'mw-academy-settings',
		'mw_analytics_section',
		array( 'key' => 'analytics_ga_id', 'description' => 'e.g. G-XXXXXXXXXX' )
	);

	add_settings_field(
		'analytics_gtm_id',
		'Google Tag Manager ID',
		'mw_render_text_field',
		'mw-academy-settings',
		'mw_analytics_section',
		array( 'key' => 'analytics_gtm_id', 'description' => 'e.g. GTM-XXXXXXX' )
	);
}
add_action( 'admin_init', 'mw_register_academy_settings' );

/**
 * Sanitize settings array.
 *
 * @param array $input Raw input.
 * @return array Sanitized output.
 */
function mw_sanitize_academy_options( $input ) {
	$sanitized = array();
	$text_keys = array(
		'contentful_space_id',
		'contentful_access_token',
		'contentful_default_image',
		'newsletter_api_key',
		'newsletter_list_id',
		'analytics_ga_id',
		'analytics_gtm_id',
	);

	foreach ( $text_keys as $key ) {
		$sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
	}

	return $sanitized;
}

/**
 * Render a text input settings field.
 *
 * @param array $args Field arguments.
 */
function mw_render_text_field( $args ) {
	$options = get_option( 'mw_academy_options', array() );
	$key     = $args['key'];
	$type    = isset( $args['type'] ) ? $args['type'] : 'text';
	$value   = isset( $options[ $key ] ) ? esc_attr( $options[ $key ] ) : '';
	$desc    = isset( $args['description'] ) ? $args['description'] : '';

	printf(
		'<input type="%s" name="mw_academy_options[%s]" value="%s" class="regular-text" />',
		esc_attr( $type ),
		esc_attr( $key ),
		$value
	);

	if ( $desc ) {
		printf( '<p class="description">%s</p>', esc_html( $desc ) );
	}
}

/**
 * Render the Academy dashboard page.
 */
function mw_academy_dashboard_page() {
	$post_types = mw_get_academy_post_types();
	?>
	<div class="wrap">
		<h1>Masterworks Academy</h1>
		<p>Welcome to the Masterworks Academy content management dashboard.</p>

		<div class="mw-academy-cards" style="display:flex;flex-wrap:wrap;gap:16px;margin-top:20px;">
			<?php foreach ( $post_types as $pt_slug ) :
				$pt_obj = get_post_type_object( $pt_slug );
				if ( ! $pt_obj ) {
					continue;
				}
				$count = wp_count_posts( $pt_slug );
				$published = isset( $count->publish ) ? $count->publish : 0;
				$draft     = isset( $count->draft ) ? $count->draft : 0;
				?>
				<div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:16px 20px;min-width:200px;flex:1;">
					<h3 style="margin:0 0 8px;">
						<span class="dashicons <?php echo esc_attr( $pt_obj->menu_icon ); ?>" style="margin-right:4px;"></span>
						<?php echo esc_html( $pt_obj->labels->name ); ?>
					</h3>
					<p style="margin:0;color:#50575e;">
						<?php echo intval( $published ); ?> published, <?php echo intval( $draft ); ?> drafts
					</p>
					<p style="margin:8px 0 0;">
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $pt_slug ) ); ?>">View All</a> |
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $pt_slug ) ); ?>">Add New</a>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Render the Academy settings page.
 */
function mw_academy_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Academy Settings</h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'mw_academy_settings' );
			do_settings_sections( 'mw-academy-settings' );
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}
