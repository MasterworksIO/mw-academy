<?php
/**
 * Masterworks Academy - wp-config.php Additions
 *
 * Paste this block into wp-config.php on the Lightsail instance ABOVE
 * the line that says: "That's all, stop editing!"
 *
 * Replace every {{PLACEHOLDER}} with real values from your infrastructure
 * provisioning outputs and Secrets Manager.
 *
 * @package MasterworksAcademy
 */

// =============================================================================
// 1. DATABASE CONNECTION
// =============================================================================
// Point to the Lightsail managed MySQL database for durability and automated
// backups. These override the defaults that Bitnami sets during instance creation.

define( 'DB_NAME',     '{{DB_NAME}}' );         // e.g., academy_wp
define( 'DB_USER',     '{{DB_USER}}' );         // e.g., dbmasteruser
define( 'DB_PASSWORD', '{{DB_PASSWORD}}' );     // Retrieve from Secrets Manager
define( 'DB_HOST',     '{{DB_HOST}}' );         // Lightsail DB endpoint, e.g., ls-xxxx.us-east-1.rds.amazonaws.com
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  'utf8mb4_unicode_520_ci' );

// Custom table prefix to avoid collisions if the database is shared.
$table_prefix = 'mwacademy_';

// =============================================================================
// 2. SITE URL - Subfolder Configuration
// =============================================================================
// WordPress lives at /academy on the public domain. Both values MUST match
// or the site will produce infinite redirects.

define( 'WP_HOME',    'https://masterworks.com/academy' );
define( 'WP_SITEURL', 'https://masterworks.com/academy' );

// =============================================================================
// 3. SSL AND REVERSE PROXY TRUST
// =============================================================================
// CloudFront (or Nginx) terminates SSL and forwards HTTP to the Lightsail origin.
// WordPress must trust the X-Forwarded-* headers to detect HTTPS and the real
// client IP address.

define( 'FORCE_SSL_ADMIN', true );

// Trust X-Forwarded-Proto from CloudFront / ALB / Nginx
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}

// Trust the real client IP (leftmost address in the forwarded chain)
if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
    $forwarded_ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
    $_SERVER['REMOTE_ADDR'] = trim( $forwarded_ips[0] );
}

// If using Nginx reverse proxy instead of CloudFront, X-Real-IP is set directly
if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_REAL_IP'];
}

// =============================================================================
// 4. ENVIRONMENT DETECTION
// =============================================================================
// WP_ENVIRONMENT_TYPE lets plugins adapt behavior per environment.
// Accepted values: 'local', 'development', 'staging', 'production'

define( 'WP_ENVIRONMENT_TYPE', '{{ENVIRONMENT}}' ); // Set to 'production' or 'staging'

// =============================================================================
// 5. DEBUGGING
// =============================================================================
// Production: everything off. Staging: log errors to file but never display.

if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'staging' === WP_ENVIRONMENT_TYPE ) {
    define( 'WP_DEBUG',         true );
    define( 'WP_DEBUG_LOG',     true );       // Writes to wp-content/debug.log
    define( 'WP_DEBUG_DISPLAY', false );      // Never show errors to visitors
    define( 'SCRIPT_DEBUG',     true );       // Use unminified core scripts
    @ini_set( 'display_errors', 0 );
} else {
    define( 'WP_DEBUG',         false );
    define( 'WP_DEBUG_LOG',     false );
    define( 'WP_DEBUG_DISPLAY', false );
    define( 'SCRIPT_DEBUG',     false );
    @ini_set( 'display_errors', 0 );
}

// =============================================================================
// 6. PERFORMANCE
// =============================================================================

// PHP memory limits
define( 'WP_MEMORY_LIMIT',     '256M' );      // Front-end
define( 'WP_MAX_MEMORY_LIMIT', '512M' );      // Admin / wp-cron

// Limit post revisions to keep the database lean
define( 'WP_POST_REVISIONS', 10 );

// Autosave interval (seconds) - slightly longer than default 60s
define( 'AUTOSAVE_INTERVAL', 120 );

// Automatic trash emptying (days)
define( 'EMPTY_TRASH_DAYS', 14 );

// =============================================================================
// 7. REDIS OBJECT CACHE (future ElastiCache integration)
// =============================================================================
// Uncomment these lines when ElastiCache Redis is provisioned.
// Requires the "Redis Object Cache" plugin by Till Kruss.

// define( 'WP_REDIS_HOST',         '{{REDIS_HOST}}' );   // e.g., mw-redis.xxxx.cache.amazonaws.com
// define( 'WP_REDIS_PORT',         6379 );
// define( 'WP_REDIS_DATABASE',     0 );
// define( 'WP_REDIS_PREFIX',       'mwacademy_' );
// define( 'WP_REDIS_TIMEOUT',      1 );
// define( 'WP_REDIS_READ_TIMEOUT', 1 );

// =============================================================================
// 8. SECURITY HARDENING
// =============================================================================

// Disable the theme/plugin file editor in wp-admin
define( 'DISALLOW_FILE_EDIT', true );

// Prevent plugin/theme installs from the admin UI.
// Uncomment in production once all plugins are deployed via WP-CLI.
// define( 'DISALLOW_FILE_MODS', true );

// Disable XML-RPC entirely (prevents brute-force amplification and DDoS)
add_filter( 'xmlrpc_enabled', '__return_false' );

// =============================================================================
// 9. AUTHENTICATION KEYS AND SALTS
// =============================================================================
// Generate at: https://api.wordpress.org/secret-key/1.1/salt/
// Each key should be a unique, random string of at least 64 characters.

define( 'AUTH_KEY',         '{{AUTH_KEY}}' );
define( 'SECURE_AUTH_KEY',  '{{SECURE_AUTH_KEY}}' );
define( 'LOGGED_IN_KEY',    '{{LOGGED_IN_KEY}}' );
define( 'NONCE_KEY',        '{{NONCE_KEY}}' );
define( 'AUTH_SALT',        '{{AUTH_SALT}}' );
define( 'SECURE_AUTH_SALT', '{{SECURE_AUTH_SALT}}' );
define( 'LOGGED_IN_SALT',   '{{LOGGED_IN_SALT}}' );
define( 'NONCE_SALT',       '{{NONCE_SALT}}' );

// =============================================================================
// 10. CRON
// =============================================================================
// Disable WP-Cron triggered by page loads. A real system cron job fires
// wp cron event run --due-now every 5 minutes instead.
// See infrastructure/setup-lightsail.sh for the crontab entry.

define( 'DISABLE_WP_CRON', true );

// =============================================================================
// 11. CONTENT DIRECTORY (optional override)
// =============================================================================
// Uncomment to relocate wp-content outside the core directory for cleaner deploys.
// This changes file paths -- update your theme and deployment scripts accordingly.

// define( 'WP_CONTENT_DIR', '/opt/bitnami/wordpress/wp-content' );
// define( 'WP_CONTENT_URL', 'https://masterworks.com/academy/wp-content' );

// =============================================================================
// END OF ADDITIONS - The next line in wp-config.php should be:
//   require_once ABSPATH . 'wp-settings.php';
// =============================================================================
