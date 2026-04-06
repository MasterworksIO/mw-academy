#!/usr/bin/env bash
# =============================================================================
# Masterworks Academy - Lightsail Instance Setup Script
#
# Run this on the Lightsail instance AFTER CloudFormation provisions it.
# It configures WordPress for the /academy subfolder, installs plugins,
# creates initial content, and hardens the server.
#
# Usage:
#   chmod +x setup-lightsail.sh
#   sudo ./setup-lightsail.sh
#
# Prerequisites:
#   - Lightsail WordPress (Bitnami) instance is running
#   - SSH access as the bitnami user
#   - Lightsail managed database is provisioned and accessible
# =============================================================================

set -euo pipefail
IFS=$'\n\t'

# =============================================================================
# Configuration - EDIT THESE before running
# =============================================================================
DOMAIN="masterworks.com"
WP_PATH="/opt/bitnami/wordpress"
WP_SUBFOLDER="academy"
WP_URL="https://${DOMAIN}/${WP_SUBFOLDER}"
WP_ADMIN_USER="{{WP_ADMIN_USER}}"           # e.g., mw-admin
WP_ADMIN_EMAIL="{{WP_ADMIN_EMAIL}}"          # e.g., dev@masterworks.com
WP_ADMIN_PASSWORD="{{WP_ADMIN_PASSWORD}}"    # Use a strong password
DB_HOST="{{DB_HOST}}"                         # Lightsail DB endpoint
DB_NAME="{{DB_NAME}}"                         # e.g., academy_wp
DB_USER="{{DB_USER}}"                         # e.g., dbmasteruser
DB_PASSWORD="{{DB_PASSWORD}}"                 # From Secrets Manager
THEME_ZIP_URL="{{THEME_ZIP_URL}}"             # URL to custom theme .zip or local path
TIMEZONE="America/New_York"

# Bitnami paths
APACHE_CONF="/opt/bitnami/apache/conf"
PHP_INI="/opt/bitnami/php/etc/php.ini"

# =============================================================================
# Helpers
# =============================================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log()  { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC}  $*"; }
err()  { echo -e "${RED}[ERROR]${NC} $*" >&2; }

# WP-CLI wrapper that runs as the correct Bitnami user
wpcli() {
    sudo -u bitnami wp --path="${WP_PATH}" "$@"
}

# =============================================================================
# 0. Pre-flight checks
# =============================================================================
if [[ $EUID -ne 0 ]]; then
    err "This script must be run as root (use sudo)."
    exit 1
fi

log "Starting Masterworks Academy Lightsail setup..."
log "WordPress path: ${WP_PATH}"
log "Target URL:     ${WP_URL}"

# =============================================================================
# 1. Update system packages
# =============================================================================
log "Updating system packages..."
apt-get update -y
apt-get upgrade -y
apt-get install -y curl unzip jq

# =============================================================================
# 2. Install WP-CLI
# =============================================================================
if ! command -v wp &>/dev/null; then
    log "Installing WP-CLI..."
    curl -sSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
    chmod +x /usr/local/bin/wp
    log "WP-CLI installed: $(wp --version)"
else
    log "WP-CLI already installed: $(wp --version)"
    log "Updating WP-CLI..."
    wp cli update --yes 2>/dev/null || true
fi

# =============================================================================
# 3. Configure database connection
# =============================================================================
log "Configuring database connection..."

# Back up the original wp-config.php
cp "${WP_PATH}/wp-config.php" "${WP_PATH}/wp-config.php.bak.$(date +%Y%m%d%H%M%S)"

wpcli config set DB_HOST     "${DB_HOST}"
wpcli config set DB_NAME     "${DB_NAME}"
wpcli config set DB_USER     "${DB_USER}"
wpcli config set DB_PASSWORD "${DB_PASSWORD}"
wpcli config set DB_CHARSET  'utf8mb4'
wpcli config set DB_COLLATE  'utf8mb4_unicode_520_ci'

log "Database connection configured."

# =============================================================================
# 4. Configure WordPress for /academy subfolder
# =============================================================================
log "Configuring WordPress for subfolder: /${WP_SUBFOLDER}..."

# Core WordPress options
wpcli option update siteurl "${WP_URL}"
wpcli option update home    "${WP_URL}"

# SEO-friendly permalink structure
wpcli rewrite structure '/%postname%/' --hard
wpcli rewrite flush --hard

# Timezone, date and time formats
wpcli option update timezone_string "${TIMEZONE}"
wpcli option update date_format     'F j, Y'
wpcli option update time_format     'g:i a'

# Site metadata
wpcli option update blogname        "Masterworks Academy"
wpcli option update blogdescription "Research, Data & Insights for the Art Market"

# Content settings
wpcli option update uploads_use_yearmonth_folders 1
wpcli option update default_comment_status        'closed'
wpcli option update default_ping_status           'closed'

log "WordPress subfolder configuration complete."

# =============================================================================
# 5. Generate security salts
# =============================================================================
log "Generating fresh security salts..."
wpcli config shuffle-salts

# =============================================================================
# 6. Apply wp-config constants
# =============================================================================
log "Setting wp-config constants..."

wpcli config set WP_MEMORY_LIMIT     '256M'
wpcli config set WP_MAX_MEMORY_LIMIT '512M'
wpcli config set FORCE_SSL_ADMIN      true   --raw
wpcli config set DISALLOW_FILE_EDIT   true   --raw
wpcli config set DISABLE_WP_CRON     true   --raw
wpcli config set WP_POST_REVISIONS   10     --raw
wpcli config set AUTOSAVE_INTERVAL   120    --raw
wpcli config set EMPTY_TRASH_DAYS    14     --raw
wpcli config set WP_DEBUG            false  --raw
wpcli config set WP_DEBUG_LOG        false  --raw
wpcli config set WP_DEBUG_DISPLAY    false  --raw
wpcli config set WP_ENVIRONMENT_TYPE 'production'

warn "You must still merge config/wp-config-additions.php into wp-config.php"
warn "for reverse proxy header trust (X-Forwarded-Proto, X-Forwarded-For)."

# =============================================================================
# 7. Install plugins
# =============================================================================
log "Installing plugins from WordPress.org..."

declare -A PLUGINS=(
    ["wordpress-seo"]="Yoast SEO"
    ["wordfence"]="Wordfence Security"
    ["redirection"]="Redirection (301 manager)"
    ["safe-svg"]="Safe SVG"
    ["wp-mail-smtp"]="WP Mail SMTP"
    ["tablepress"]="TablePress"
    ["code-snippets"]="Code Snippets"
)

for slug in "${!PLUGINS[@]}"; do
    name="${PLUGINS[$slug]}"
    if wpcli plugin is-installed "${slug}" 2>/dev/null; then
        log "  ${name} already installed, activating..."
        wpcli plugin activate "${slug}" || warn "  Could not activate ${name}"
    else
        log "  Installing ${name}..."
        wpcli plugin install "${slug}" --activate || warn "  Failed to install ${name}"
    fi
done

# Premium plugins -- must be uploaded manually or from a private URL
log ""
log "Premium plugins require manual installation:"
log "  - Advanced Custom Fields PRO"
log "    wp plugin install /path/to/advanced-custom-fields-pro.zip --activate --path=${WP_PATH}"
log "  - WP Rocket"
log "    wp plugin install /path/to/wp-rocket.zip --activate --path=${WP_PATH}"
log ""

# Remove default unused plugins
log "Removing default Bitnami plugins..."
wpcli plugin deactivate hello 2>/dev/null || true
wpcli plugin delete hello 2>/dev/null || true
wpcli plugin deactivate akismet 2>/dev/null || true

# =============================================================================
# 8. Install custom theme
# =============================================================================
log "Installing custom theme..."

if [[ -n "${THEME_ZIP_URL}" && "${THEME_ZIP_URL}" != "{{THEME_ZIP_URL}}" ]]; then
    wpcli theme install "${THEME_ZIP_URL}" --activate
    log "Custom theme installed and activated."
else
    warn "No theme URL provided. Install manually:"
    warn "  wp theme install /path/to/theme.zip --activate --path=${WP_PATH}"
fi

# Clean up default themes (keep one as fallback)
log "Removing unused default themes..."
wpcli theme delete twentytwentytwo 2>/dev/null || true
wpcli theme delete twentytwentythree 2>/dev/null || true
# Keep twentytwentyfour as a fallback

# =============================================================================
# 9. Create initial pages
# =============================================================================
log "Creating initial pages..."

HOME_PAGE_ID=$(wpcli post create \
    --post_type=page \
    --post_title="Academy Home" \
    --post_status=publish \
    --post_name="academy-home" \
    --porcelain)
log "  Created 'Academy Home' (ID: ${HOME_PAGE_ID})"

RESEARCH_ID=$(wpcli post create \
    --post_type=page \
    --post_title="Research" \
    --post_status=publish \
    --post_name="research" \
    --porcelain)
log "  Created 'Research' (ID: ${RESEARCH_ID})"

DATA_ID=$(wpcli post create \
    --post_type=page \
    --post_title="Data" \
    --post_status=publish \
    --post_name="data" \
    --porcelain)
log "  Created 'Data' (ID: ${DATA_ID})"

NEWS_ID=$(wpcli post create \
    --post_type=page \
    --post_title="Daily News" \
    --post_status=publish \
    --post_name="daily-news" \
    --porcelain)
log "  Created 'Daily News' (ID: ${NEWS_ID})"

# Set front page to static page
wpcli option update show_on_front 'page'
wpcli option update page_on_front "${HOME_PAGE_ID}"

log "Front page set to 'Academy Home'."

# =============================================================================
# 10. Create navigation menu
# =============================================================================
log "Creating navigation menu..."

MENU_ID=$(wpcli menu create "Academy Main Menu" --porcelain 2>/dev/null || echo "")
if [[ -n "${MENU_ID}" ]]; then
    wpcli menu item add-post "${MENU_ID}" "${HOME_PAGE_ID}" --title="Home" 2>/dev/null || true
    wpcli menu item add-post "${MENU_ID}" "${RESEARCH_ID}"  --title="Research" 2>/dev/null || true
    wpcli menu item add-post "${MENU_ID}" "${DATA_ID}"      --title="Data" 2>/dev/null || true
    wpcli menu item add-post "${MENU_ID}" "${NEWS_ID}"      --title="Daily News" 2>/dev/null || true
    wpcli menu location assign "${MENU_ID}" primary 2>/dev/null || true
    log "Navigation menu created and assigned."
else
    warn "Could not create navigation menu. Set it up manually in wp-admin."
fi

# =============================================================================
# 11. Set up system cron for WordPress
# =============================================================================
log "Configuring system cron for WordPress..."

CRON_LINE="*/5 * * * * bitnami cd ${WP_PATH} && /usr/local/bin/wp cron event run --due-now --path=${WP_PATH} --quiet >/dev/null 2>&1"

echo "${CRON_LINE}" > /etc/cron.d/wordpress-cron
chmod 644 /etc/cron.d/wordpress-cron

log "WordPress cron scheduled via /etc/cron.d/wordpress-cron (every 5 minutes)."

# =============================================================================
# 12. Harden file permissions
# =============================================================================
log "Hardening file permissions..."

# Ownership
chown -R bitnami:daemon "${WP_PATH}"

# Directories: 755 (rwxr-xr-x)
find "${WP_PATH}" -type d -exec chmod 755 {} \;

# Files: 644 (rw-r--r--)
find "${WP_PATH}" -type f -exec chmod 644 {} \;

# wp-config.php: owner-only read
chmod 600 "${WP_PATH}/wp-config.php"

# Writable directories for uploads and plugin/theme management
chmod 775 "${WP_PATH}/wp-content"
chmod 775 "${WP_PATH}/wp-content/uploads"
chmod 775 "${WP_PATH}/wp-content/plugins"
chmod 775 "${WP_PATH}/wp-content/themes"

# .htaccess must be writable for permalink flushes
chmod 664 "${WP_PATH}/.htaccess"

log "File permissions set."

# =============================================================================
# 13. Configure Apache for /academy subfolder
# =============================================================================
log "Configuring Apache for /academy..."

cat > "${APACHE_CONF}/bitnami/bitnami-apps-prefix.conf" << 'APACHEEOF'
# Masterworks Academy - Map /academy to WordPress document root
Alias /academy "/opt/bitnami/wordpress"

<Directory "/opt/bitnami/wordpress">
    Options -Indexes +FollowSymLinks -MultiViews
    AllowOverride All
    Require all granted

    <IfModule mod_rewrite.c>
        RewriteEngine On
    </IfModule>
</Directory>
APACHEEOF

log "Apache alias configured."

# =============================================================================
# 14. Tune PHP settings
# =============================================================================
log "Tuning PHP settings..."

if [[ -f "${PHP_INI}" ]]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 64M/'   "${PHP_INI}"
    sed -i 's/^post_max_size = .*/post_max_size = 64M/'             "${PHP_INI}"
    sed -i 's/^memory_limit = .*/memory_limit = 256M/'              "${PHP_INI}"
    sed -i 's/^max_execution_time = .*/max_execution_time = 300/'   "${PHP_INI}"
    sed -i 's/^max_input_time = .*/max_input_time = 300/'           "${PHP_INI}"
    log "PHP settings updated."
else
    warn "php.ini not found at ${PHP_INI}. Adjust PHP settings manually."
fi

# =============================================================================
# 15. Restart Apache
# =============================================================================
log "Restarting Apache..."
/opt/bitnami/ctlscript.sh restart apache 2>/dev/null || \
    systemctl restart apache2 2>/dev/null || \
    systemctl restart httpd 2>/dev/null || \
    warn "Could not restart Apache. Restart manually."

# =============================================================================
# 16. Verification
# =============================================================================
log "Running verification checks..."

# Test HTTP response
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/academy/" 2>/dev/null || echo "000")
if [[ "${HTTP_CODE}" =~ ^(200|301|302)$ ]]; then
    log "WordPress responding (HTTP ${HTTP_CODE})."
else
    warn "WordPress returned HTTP ${HTTP_CODE}. Check Apache config and wp-config.php."
fi

# Verify WP-CLI can read the database
if wpcli option get siteurl &>/dev/null; then
    log "WP-CLI confirmed siteurl: $(wpcli option get siteurl)"
else
    warn "WP-CLI could not read siteurl. Check database connectivity."
fi

# List active plugins
log ""
log "Active plugins:"
wpcli plugin list --status=active --format=table

# List themes
log ""
log "Installed themes:"
wpcli theme list --format=table

# =============================================================================
# Done
# =============================================================================
echo ""
log "============================================"
log "  Masterworks Academy setup complete!"
log "============================================"
log ""
log "  Site URL:    ${WP_URL}"
log "  Admin URL:   ${WP_URL}/wp-admin/"
log "  Admin user:  ${WP_ADMIN_USER}"
log ""
log "  Remaining steps:"
log "    1. Merge config/wp-config-additions.php into wp-config.php"
log "    2. Copy config/.htaccess to ${WP_PATH}/.htaccess"
log "    3. Install premium plugins (ACF Pro, WP Rocket)"
log "    4. Upload custom theme if not provided via URL"
log "    5. Update DNS / validate CloudFront distribution"
log "    6. Test: https://masterworks.com/academy/"
log ""
