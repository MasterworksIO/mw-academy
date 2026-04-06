<?php
/**
 * WordPress Customizer Settings
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Customizer settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
 */
function mw_academy_customize_register( $wp_customize ) {

    // =========================================================================
    // Panel: Academy Settings
    // =========================================================================
    $wp_customize->add_panel( 'mw_academy_panel', array(
        'title'       => esc_html__( 'Academy Settings', 'masterworks-academy' ),
        'description' => esc_html__( 'Configure Masterworks Academy theme settings.', 'masterworks-academy' ),
        'priority'    => 30,
    ) );

    // =========================================================================
    // Section: Hero / Featured Content
    // =========================================================================
    $wp_customize->add_section( 'mw_hero_section', array(
        'title'    => esc_html__( 'Hero / Featured Content', 'masterworks-academy' ),
        'panel'    => 'mw_academy_panel',
        'priority' => 10,
    ) );

    // Featured post selection
    $wp_customize->add_setting( 'mw_featured_post', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'mw_featured_post', array(
        'label'       => esc_html__( 'Featured Post ID', 'masterworks-academy' ),
        'description' => esc_html__( 'Enter the post ID to feature in the hero section. Leave 0 to use the latest sticky post or most recent post.', 'masterworks-academy' ),
        'section'     => 'mw_hero_section',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 0,
            'step' => 1,
        ),
    ) );

    // Hero subtitle
    $wp_customize->add_setting( 'mw_hero_subtitle', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'mw_hero_subtitle', array(
        'label'   => esc_html__( 'Hero Subtitle', 'masterworks-academy' ),
        'section' => 'mw_hero_section',
        'type'    => 'text',
    ) );

    // =========================================================================
    // Section: Newsletter Integration
    // =========================================================================
    $wp_customize->add_section( 'mw_newsletter_section', array(
        'title'    => esc_html__( 'Newsletter Integration', 'masterworks-academy' ),
        'panel'    => 'mw_academy_panel',
        'priority' => 20,
    ) );

    // Newsletter provider
    $wp_customize->add_setting( 'mw_newsletter_provider', array(
        'default'           => 'none',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'mw_newsletter_provider', array(
        'label'   => esc_html__( 'Newsletter Provider', 'masterworks-academy' ),
        'section' => 'mw_newsletter_section',
        'type'    => 'select',
        'choices' => array(
            'none'       => esc_html__( 'None', 'masterworks-academy' ),
            'mailchimp'  => esc_html__( 'Mailchimp', 'masterworks-academy' ),
            'convertkit' => esc_html__( 'ConvertKit', 'masterworks-academy' ),
        ),
    ) );

    // Mailchimp API Key
    $wp_customize->add_setting( 'mw_mailchimp_api_key', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'mw_mailchimp_api_key', array(
        'label'       => esc_html__( 'Mailchimp API Key', 'masterworks-academy' ),
        'description' => esc_html__( 'Required for Mailchimp integration.', 'masterworks-academy' ),
        'section'     => 'mw_newsletter_section',
        'type'        => 'text',
    ) );

    // Mailchimp List ID
    $wp_customize->add_setting( 'mw_mailchimp_list_id', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'mw_mailchimp_list_id', array(
        'label'   => esc_html__( 'Mailchimp List/Audience ID', 'masterworks-academy' ),
        'section' => 'mw_newsletter_section',
        'type'    => 'text',
    ) );

    // ConvertKit API Key
    $wp_customize->add_setting( 'mw_convertkit_api_key', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'mw_convertkit_api_key', array(
        'label'       => esc_html__( 'ConvertKit API Key', 'masterworks-academy' ),
        'description' => esc_html__( 'Required for ConvertKit integration.', 'masterworks-academy' ),
        'section'     => 'mw_newsletter_section',
        'type'        => 'text',
    ) );

    // ConvertKit Form ID
    $wp_customize->add_setting( 'mw_convertkit_form_id', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'mw_convertkit_form_id', array(
        'label'   => esc_html__( 'ConvertKit Form ID', 'masterworks-academy' ),
        'section' => 'mw_newsletter_section',
        'type'    => 'text',
    ) );

    // =========================================================================
    // Section: Social Media
    // =========================================================================
    $wp_customize->add_section( 'mw_social_section', array(
        'title'    => esc_html__( 'Social Media', 'masterworks-academy' ),
        'panel'    => 'mw_academy_panel',
        'priority' => 30,
    ) );

    $social_platforms = array(
        'twitter'   => __( 'X (Twitter) URL', 'masterworks-academy' ),
        'linkedin'  => __( 'LinkedIn URL', 'masterworks-academy' ),
        'instagram' => __( 'Instagram URL', 'masterworks-academy' ),
        'youtube'   => __( 'YouTube URL', 'masterworks-academy' ),
        'facebook'  => __( 'Facebook URL', 'masterworks-academy' ),
    );

    foreach ( $social_platforms as $platform => $label ) {
        $wp_customize->add_setting( 'mw_social_' . $platform, array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ) );

        $wp_customize->add_control( 'mw_social_' . $platform, array(
            'label'   => $label,
            'section' => 'mw_social_section',
            'type'    => 'url',
        ) );
    }

    // =========================================================================
    // Section: Footer
    // =========================================================================
    $wp_customize->add_section( 'mw_footer_section', array(
        'title'    => esc_html__( 'Footer', 'masterworks-academy' ),
        'panel'    => 'mw_academy_panel',
        'priority' => 40,
    ) );

    // Footer text
    $wp_customize->add_setting( 'mw_footer_text', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'mw_footer_text', array(
        'label'       => esc_html__( 'Footer Copyright Text', 'masterworks-academy' ),
        'description' => esc_html__( 'Custom copyright text. Leave empty for default.', 'masterworks-academy' ),
        'section'     => 'mw_footer_section',
        'type'        => 'textarea',
    ) );

    // =========================================================================
    // Section: Analytics & Tracking
    // =========================================================================
    $wp_customize->add_section( 'mw_analytics_section', array(
        'title'    => esc_html__( 'Analytics & Tracking', 'masterworks-academy' ),
        'panel'    => 'mw_academy_panel',
        'priority' => 50,
    ) );

    // Google Analytics Measurement ID
    $wp_customize->add_setting( 'mw_ga_id', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );

    $wp_customize->add_control( 'mw_ga_id', array(
        'label'       => esc_html__( 'Google Analytics Measurement ID', 'masterworks-academy' ),
        'description' => esc_html__( 'Enter your GA4 Measurement ID (e.g., G-XXXXXXXXXX).', 'masterworks-academy' ),
        'section'     => 'mw_analytics_section',
        'type'        => 'text',
    ) );
}
add_action( 'customize_register', 'mw_academy_customize_register' );

/**
 * Output Google Analytics tracking code in the head.
 */
function mw_academy_analytics_head() {
    $ga_id = get_theme_mod( 'mw_ga_id', '' );
    if ( empty( $ga_id ) || is_customize_preview() ) {
        return;
    }

    // Don't track logged-in admins
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_js( $ga_id ); ?>');
    </script>
    <?php
}
add_action( 'wp_head', 'mw_academy_analytics_head', 1 );

/**
 * Selective refresh transport for Customizer.
 */
function mw_academy_customize_preview_js() {
    wp_enqueue_script(
        'mw-academy-customizer-preview',
        MW_ACADEMY_URI . '/assets/js/customizer-preview.js',
        array( 'customize-preview' ),
        MW_ACADEMY_VERSION,
        true
    );
}
add_action( 'customize_preview_init', 'mw_academy_customize_preview_js' );
