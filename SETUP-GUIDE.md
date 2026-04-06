# Masterworks Academy — Step-by-Step Setup Guide

**Starting point:** You have nothing. No server, no WordPress, no config.
**End state:** WordPress running at `masterworks.com/academy` with all custom content types, D3.js visualizations, Contentful integration, and SEO optimization.

---

## Prerequisites (Get These First)

Before you touch anything, gather these credentials and tools:

- [ ] **AWS Console access** — Login at https://console.aws.amazon.com
- [ ] **GoDaddy account** — Login at https://dcc.godaddy.com/manage-dns (for DNS)
- [ ] **ACF Pro license** — Purchase at https://www.advancedcustomfields.com/pro/ ($49/yr)
- [ ] **WP Rocket license** — Purchase at https://wp-rocket.me ($59/yr) (optional but recommended)
- [ ] **Contentful Space ID and API Access Token** — Get from your Contentful dashboard under Settings > API Keys
- [ ] **SSH key pair** — You'll need this to connect to the Lightsail instance
- [ ] Your existing React app's **origin domain** (the hostname CloudFront or your load balancer currently points to)

---

## STEP 1: Create the Lightsail WordPress Instance

**Time: ~10 minutes**

### 1a. Open AWS Lightsail

1. Go to https://lightsail.aws.amazon.com
2. Make sure you're in **us-east-1 (N. Virginia)** — click the region dropdown top-right
3. Click **Create instance**

### 1b. Configure the Instance

1. **Platform:** Linux/Unix
2. **Blueprint:** Click "Apps + OS" tab, select **WordPress**
3. **Instance plan:** Select **$20/month** (4 GB RAM, 2 vCPUs, 80 GB SSD)
   - This handles medium traffic. You can upgrade later with one click.
4. **Instance name:** `mw-academy-production`
5. **Key pair:** Select your existing SSH key or create a new one (download and save it)
6. Click **Create instance**

Wait 2-3 minutes for the instance to show "Running".

### 1c. Assign a Static IP

1. In Lightsail, go to **Networking** tab
2. Click **Create static IP**
3. Name it: `mw-academy-ip`
4. **Attach to instance:** Select `mw-academy-production`
5. Click **Create**
6. **Write down the static IP address** — you'll need it later (e.g., `3.89.xx.xx`)

### 1d. Open the Firewall

1. Click on your instance → **Networking** tab
2. Under IPv4 Firewall, confirm these ports are open:
   - **SSH (22)** — already open by default
   - **HTTP (80)** — already open by default
   - **HTTPS (443)** — already open by default

### 1e. Get Your WordPress Admin Password

1. Click on your instance name
2. Click **Connect using SSH** (browser-based terminal)
3. Run:
   ```bash
   cat /home/bitnami/bitnami_credentials
   ```
4. **Write down the username and password** — this is your temporary WordPress admin login

---

## STEP 2: Create the Managed Database

**Time: ~15 minutes (includes provisioning wait)**

### 2a. Create Database

1. In Lightsail, click **Databases** in the left sidebar
2. Click **Create database**
3. **Database engine:** MySQL 8.0
4. **Plan:** Standard ($15/month) — enough to start
5. **Master username:** `dbmasteruser`
6. **Master password:** Generate a strong password. **Save it in a password manager.**
7. **Database name:** `academy_wp`
8. **Instance name:** `mw-academy-db`
9. **Availability zone:** Same as your instance (us-east-1a)
10. Click **Create database**

This takes ~10 minutes to provision. You'll see "Available" when ready.

### 2b. Get the Database Endpoint

1. Click on your database name
2. Under **Connection details**, find the **Endpoint** — it looks like:
   ```
   ls-xxxxxxxxx.xxxxxxxx.us-east-1.rds.amazonaws.com
   ```
3. **Write this down** — this is your `DB_HOST`

### 2c. Enable Data Import Mode (Faster Setup)

1. On the database page, click **Networking** tab
2. Toggle **Public mode** to ON temporarily (we'll turn it off after setup)
   - This lets your Lightsail instance connect

---

## STEP 3: SSH Into the Instance and Run Setup

**Time: ~15 minutes**

### 3a. Connect via SSH

**Option A: Browser SSH** — Click "Connect using SSH" in the Lightsail console

**Option B: Terminal SSH:**
```bash
ssh -i /path/to/your-key.pem bitnami@YOUR_STATIC_IP
```

### 3b. Upload the Project Files

From your local machine, upload the project to the instance:

```bash
# From your local terminal (not the SSH session)
cd /Users/jacknorman/Desktop/claudeprojects/academy

# Create a zip of everything
zip -r academy-project.zip . -x ".claude/*" "*.DS_Store"

# Upload to the instance
scp -i /path/to/your-key.pem academy-project.zip bitnami@YOUR_STATIC_IP:/home/bitnami/
```

### 3c. Unzip on the Instance

Back in the SSH session:
```bash
cd /home/bitnami
unzip academy-project.zip -d academy-project
```

### 3d. Edit the Setup Script

```bash
nano /home/bitnami/academy-project/infrastructure/setup-lightsail.sh
```

Replace these placeholders at the top of the file:

| Placeholder | Replace With |
|-------------|-------------|
| `{{WP_ADMIN_USER}}` | `mw-admin` (or whatever you want) |
| `{{WP_ADMIN_EMAIL}}` | `your-email@masterworks.com` |
| `{{WP_ADMIN_PASSWORD}}` | A strong password (save it!) |
| `{{DB_HOST}}` | The database endpoint from Step 2b |
| `{{DB_NAME}}` | `academy_wp` |
| `{{DB_USER}}` | `dbmasteruser` |
| `{{DB_PASSWORD}}` | The password from Step 2a |
| `{{THEME_ZIP_URL}}` | Leave as-is for now (we'll install the theme manually) |

Save: `Ctrl+O`, then `Enter`, then `Ctrl+X`

### 3e. Run the Setup Script

```bash
chmod +x /home/bitnami/academy-project/infrastructure/setup-lightsail.sh
sudo /home/bitnami/academy-project/infrastructure/setup-lightsail.sh
```

This will:
- Update system packages
- Install WP-CLI
- Connect WordPress to your managed database
- Set the site URL to `https://masterworks.com/academy`
- Install 7 free plugins (Yoast SEO, Wordfence, Redirection, Safe SVG, WP Mail SMTP, TablePress, Code Snippets)
- Create initial pages (Academy Home, Research, Data, Daily News)
- Set up navigation menu
- Configure cron, file permissions, and Apache

Watch the output. It should end with "Masterworks Academy setup complete!"

### 3f. Install the Custom Theme

```bash
# Copy theme to WordPress themes directory
sudo cp -r /home/bitnami/academy-project/wordpress-theme/masterworks-academy /opt/bitnami/wordpress/wp-content/themes/

# Set proper ownership
sudo chown -R bitnami:daemon /opt/bitnami/wordpress/wp-content/themes/masterworks-academy

# Activate it
sudo -u bitnami wp theme activate masterworks-academy --path=/opt/bitnami/wordpress
```

### 3g. Install the Custom Plugins

```bash
# Copy all 4 custom plugins
sudo cp -r /home/bitnami/academy-project/plugins/mw-content-types /opt/bitnami/wordpress/wp-content/plugins/
sudo cp -r /home/bitnami/academy-project/plugins/mw-data-visualizations /opt/bitnami/wordpress/wp-content/plugins/
sudo cp -r /home/bitnami/academy-project/plugins/mw-contentful-integration /opt/bitnami/wordpress/wp-content/plugins/
sudo cp -r /home/bitnami/academy-project/plugins/mw-seo-schema /opt/bitnami/wordpress/wp-content/plugins/

# Set ownership
sudo chown -R bitnami:daemon /opt/bitnami/wordpress/wp-content/plugins/mw-*

# Activate all 4
sudo -u bitnami wp plugin activate mw-content-types --path=/opt/bitnami/wordpress
sudo -u bitnami wp plugin activate mw-data-visualizations --path=/opt/bitnami/wordpress
sudo -u bitnami wp plugin activate mw-contentful-integration --path=/opt/bitnami/wordpress
sudo -u bitnami wp plugin activate mw-seo-schema --path=/opt/bitnami/wordpress
```

### 3h. Install ACF Pro (Premium Plugin)

1. Download the ACF Pro .zip from your ACF account at https://www.advancedcustomfields.com/my-account/
2. From your local machine:
   ```bash
   scp -i /path/to/your-key.pem ~/Downloads/advanced-custom-fields-pro.zip bitnami@YOUR_STATIC_IP:/home/bitnami/
   ```
3. On the instance:
   ```bash
   sudo -u bitnami wp plugin install /home/bitnami/advanced-custom-fields-pro.zip --activate --path=/opt/bitnami/wordpress
   ```

### 3i. Merge the wp-config Additions

```bash
# Back up current wp-config
sudo cp /opt/bitnami/wordpress/wp-config.php /opt/bitnami/wordpress/wp-config.php.backup

# Edit wp-config
sudo nano /opt/bitnami/wordpress/wp-config.php
```

Find the line that says:
```php
/* That's all, stop editing! Happy publishing. */
```

**ABOVE that line**, paste the contents of `config/wp-config-additions.php`. But since the setup script already set most of these values, you only need to add the **reverse proxy header trust** section. Add these lines:

```php
// Trust X-Forwarded-Proto from CloudFront
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}

// Trust the real client IP
if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
    $forwarded_ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
    $_SERVER['REMOTE_ADDR'] = trim( $forwarded_ips[0] );
}
```

Save and exit.

### 3j. Copy the .htaccess

```bash
sudo cp /home/bitnami/academy-project/config/.htaccess /opt/bitnami/wordpress/.htaccess
sudo chown bitnami:daemon /opt/bitnami/wordpress/.htaccess
sudo chmod 664 /opt/bitnami/wordpress/.htaccess
```

### 3k. Restart Apache

```bash
sudo /opt/bitnami/ctlscript.sh restart apache
```

### 3l. Test Locally

```bash
curl -I http://localhost/academy/
```

You should see `HTTP/1.1 200 OK` or `HTTP/1.1 302 Found`. If you see 200, WordPress is running.

---

## STEP 4: Set Up the SSL Certificate

**Time: ~5 minutes**

### 4a. Request a Certificate in ACM

1. Go to https://console.aws.amazon.com/acm/ (make sure you're in **us-east-1**)
2. If you already have a cert for `masterworks.com`, skip to Step 5. If not:
3. Click **Request a certificate**
4. Choose **Public certificate**
5. Domain name: `masterworks.com`
6. Add another name: `*.masterworks.com` (wildcard, useful for future subdomains)
7. Validation method: **DNS validation**
8. Click **Request**

### 4b. Validate the Certificate via GoDaddy DNS

1. In ACM, click on your pending certificate
2. You'll see a CNAME record you need to add for validation
3. Go to GoDaddy DNS: https://dcc.godaddy.com/manage-dns
4. Select `masterworks.com`
5. Add a **CNAME record** with:
   - **Name:** The `_xxxxx` part ACM shows (without the domain suffix)
   - **Value:** The `_xxxxx.acm-validations.aws` value ACM shows
6. Wait 5-30 minutes for ACM to validate and issue the certificate
7. Status will change to **Issued**
8. **Copy the certificate ARN** — looks like `arn:aws:acm:us-east-1:123456789012:certificate/abc-123`

---

## STEP 5: Set Up CloudFront (Reverse Proxy)

**Time: ~20 minutes**

This is the key step — CloudFront routes `/academy/*` to your WordPress instance while everything else goes to your React app.

### Option A: Use CloudFormation (Automated)

If you want to automate this:

```bash
# From your local machine
aws cloudformation create-stack \
  --stack-name masterworks-academy \
  --template-body file:///Users/jacknorman/Desktop/claudeprojects/academy/infrastructure/cloudformation.yaml \
  --parameters \
    ParameterKey=ReactAppOriginDomain,ParameterValue=YOUR_REACT_APP_DOMAIN \
    ParameterKey=AcmCertificateArn,ParameterValue=YOUR_ACM_CERT_ARN \
    ParameterKey=DatabaseMasterPassword,ParameterValue=YOUR_DB_PASSWORD
```

Replace:
- `YOUR_REACT_APP_DOMAIN` — the domain of your current React app origin
- `YOUR_ACM_CERT_ARN` — the certificate ARN from Step 4
- `YOUR_DB_PASSWORD` — the database password from Step 2

Then skip to Step 5d.

### Option B: Manual CloudFront Setup (If You Have an Existing Distribution)

If you already have a CloudFront distribution for masterworks.com:

### 5a. Add the WordPress Origin

1. Go to https://console.aws.amazon.com/cloudfront/
2. Click on your existing distribution
3. Go to the **Origins** tab
4. Click **Create origin**
5. Configure:
   - **Origin domain:** Enter your Lightsail static IP (e.g., `3.89.xx.xx`)
   - **Protocol:** HTTP only (SSL terminates at CloudFront)
   - **HTTP port:** 80
   - **Origin name:** `AcademyWordPress`
   - Under **Add custom header:**
     - Header name: `X-Forwarded-Host` / Value: `masterworks.com`
6. Click **Create origin**

### 5b. Add Cache Behaviors

You need to add these behaviors **in this order** (order matters — CloudFront evaluates top to bottom):

**Behavior 1: wp-admin (no cache)**
1. Click **Behaviors** tab → **Create behavior**
2. Path pattern: `/academy/wp-admin/*`
3. Origin: `AcademyWordPress`
4. Viewer protocol: Redirect HTTP to HTTPS
5. Allowed HTTP methods: GET, HEAD, OPTIONS, PUT, PATCH, POST, DELETE
6. Cache policy: **CachingDisabled**
7. Origin request policy: **AllViewer**
8. Click **Create behavior**

**Behavior 2: wp-login (no cache)**
- Path pattern: `/academy/wp-login.php`
- Same settings as wp-admin

**Behavior 3: wp-json REST API (no cache)**
- Path pattern: `/academy/wp-json/*`
- Same settings as wp-admin

**Behavior 4: Static uploads (cache aggressively)**
1. Path pattern: `/academy/wp-content/uploads/*`
2. Origin: `AcademyWordPress`
3. Allowed HTTP methods: GET, HEAD, OPTIONS
4. Cache policy: **CachingOptimized**
5. Click **Create behavior**

**Behavior 5: Theme/plugin assets (cache aggressively)**
- Path pattern: `/academy/wp-content/themes/*`
- Same as uploads

**Behavior 6: WordPress includes (cache aggressively)**
- Path pattern: `/academy/wp-includes/*`
- Same as uploads

**Behavior 7: All other /academy/ routes (no cache — let WP Rocket handle it)**
1. Path pattern: `/academy/*`
2. Origin: `AcademyWordPress`
3. Allowed HTTP methods: GET, HEAD, OPTIONS, PUT, PATCH, POST, DELETE
4. Cache policy: **CachingDisabled**
5. Origin request policy: **AllViewer**
6. Click **Create behavior**

### 5c. Wait for Distribution to Deploy

CloudFront takes 5-15 minutes to deploy changes. Status will change from "Deploying" to the last modified date.

### 5d. Test the Route

Open in your browser:
```
https://masterworks.com/academy/
```

You should see the WordPress site. If you get an error:
- Check CloudFront behavior order (wp-admin must be ABOVE the catch-all /academy/*)
- Check the Lightsail firewall allows port 80
- Check wp-config.php has the correct WP_HOME/WP_SITEURL

---

## STEP 6: Update DNS (GoDaddy)

**Time: ~5 minutes**

If you created a **new** CloudFront distribution (Option A), you need to point your domain to it:

1. Go to GoDaddy DNS: https://dcc.godaddy.com/manage-dns
2. Select `masterworks.com`
3. Find the **A record** or **CNAME** for `masterworks.com`
4. Change it to point to your CloudFront distribution domain (e.g., `d1234abcdef8.cloudfront.net`)
   - If using an A record: You'll need to use **Route 53 Alias** instead (GoDaddy CNAMEs can't be at the zone apex). Either:
     - Transfer DNS to Route 53 (recommended), or
     - Use GoDaddy's **CNAME Flattening** if available, or
     - Add a `www` CNAME to CloudFront and redirect the apex

If you're using an **existing** CloudFront distribution (Option B), DNS is already set — no changes needed.

---

## STEP 7: Configure WordPress Admin

**Time: ~15 minutes**

### 7a. Log In

Go to: `https://masterworks.com/academy/wp-admin/`

Log in with the credentials you set in the setup script.

### 7b. Configure Yoast SEO

1. Go to **Yoast SEO > General** → Run the configuration wizard
2. Set organization name: "Masterworks"
3. Set organization logo
4. Go to **Yoast SEO > Settings > Site features** → Enable XML Sitemaps
5. Go to **Yoast SEO > Settings > Content types** → Enable SEO for all custom post types
6. Submit sitemap to Google Search Console: `https://masterworks.com/academy/sitemap_index.xml`

### 7c. Configure Wordfence

1. Go to **Wordfence > All Options**
2. Enter your email for security alerts
3. Enable: brute force protection, rate limiting, 2FA for admin
4. Under Firewall: set to "Learning Mode" for the first week, then switch to "Enabled and Protecting"

### 7d. Configure Academy Settings

1. Go to **Academy > Settings** (in the left sidebar)
2. Enter your **Contentful Space ID** and **Access Token**
3. Enter your **Google Analytics Measurement ID** (GA4)
4. Save

### 7e. Configure Permalinks

1. Go to **Settings > Permalinks**
2. Select **Post name** (`/%postname%/`)
3. Click **Save Changes** (this flushes rewrite rules)

### 7f. Assign the Homepage

1. Go to **Settings > Reading**
2. Set "Your homepage displays" to **A static page**
3. Homepage: Select **Academy Home**
4. Click **Save Changes**

### 7g. Set Up the Navigation Menu

1. Go to **Appearance > Menus**
2. If the "Academy Main Menu" exists, verify it has the right items
3. If not, create it with:
   - Home → /academy/
   - Research → /academy/research/
   - Data & Indices → /academy/data/
   - Opinions → /academy/market-commentary/ (or a custom link)
   - Daily News → /academy/daily-news/
   - Culture → /academy/cultural-update/
4. Assign to the **Academy Main** menu location
5. Click **Save Menu**

---

## STEP 8: Verify Everything Works

### 8a. Test These URLs

Open each in your browser and confirm they load:

| URL | Should Show |
|-----|-------------|
| `masterworks.com/academy/` | Academy homepage with hero + content grid |
| `masterworks.com/academy/wp-admin/` | WordPress admin dashboard |
| `masterworks.com/academy/research/` | Research reports archive |
| `masterworks.com/academy/artists/` | Artist dossiers archive |
| `masterworks.com/academy/data/` | Data & Indices archive |
| `masterworks.com/academy/daily-news/` | Daily news feed |
| `masterworks.com` | Your existing React app (unchanged) |
| `masterworks.com/any-react-route` | Your existing React app (unchanged) |

### 8b. Test the REST API

```
https://masterworks.com/academy/wp-json/mw-academy/v1/content
```

Should return a JSON response (empty array is fine if no content yet).

### 8c. Test SSL

Check for mixed content warnings in Chrome DevTools (Console tab). Everything should load over HTTPS.

### 8d. Run Google PageSpeed

1. Go to https://pagespeed.web.dev/
2. Test `https://masterworks.com/academy/`
3. Target scores: 90+ Performance, 90+ SEO, 90+ Accessibility

### 8e. Submit to Google Search Console

1. Go to https://search.google.com/search-console
2. Add property: `https://masterworks.com/academy/`
3. Submit the sitemap: `https://masterworks.com/academy/sitemap_index.xml`

---

## STEP 9: Publish Your First Content

### 9a. Create a Test Research Report

1. In wp-admin, go to **Academy > Research Reports > Add New**
2. Title: "Masterworks Art Market Index — Q1 2026"
3. Select Content Pillar: "Research"
4. Select Audience: "Existing Investors"
5. Fill in the ACF fields:
   - Report Type: "Quarterly Index"
   - Publication Date: Today
   - Key Findings: Add 3-4 metrics
6. Write body content in the block editor
7. Add a D3.js chart: Click "+" → search "Art Market Chart" → configure it
8. Set a featured image
9. Click **Publish**

### 9b. Create a Test Artist Dossier

1. Go to **Academy > Artist Dossiers > Add New**
2. Fill in the artist details via ACF fields
3. Publish

### 9c. Verify the Homepage

Go to `masterworks.com/academy/` — your new content should appear in the grid.

---

## STEP 10: Lock Down for Production

### 10a. Secure the Database

1. In Lightsail, go to your database
2. Turn **OFF** public mode (Networking tab)
3. The Lightsail instance connects via the private network

### 10b. Install WP Rocket (Optional)

1. Upload the WP Rocket .zip via wp-admin > Plugins > Add New > Upload
2. Activate it
3. Go to WP Rocket settings:
   - Enable page caching
   - Enable minification (CSS + JS)
   - Enable lazy loading for images
   - Enable database optimization

### 10c. Set Up Automated Backups

1. Lightsail instance: Go to **Snapshots** tab → Enable automatic snapshots
2. Database: Already configured for daily automated backups (set in Step 2)

### 10d. Set Up Monitoring

1. In Lightsail, click your instance → **Metrics** tab
2. Create alarms for CPU > 80% and Status Check Failed

---

## Quick Reference

| What | Where |
|------|-------|
| WordPress admin | `masterworks.com/academy/wp-admin/` |
| Site URL | `masterworks.com/academy/` |
| SSH access | `ssh -i key.pem bitnami@YOUR_STATIC_IP` |
| WordPress files | `/opt/bitnami/wordpress/` on the instance |
| Theme files | `/opt/bitnami/wordpress/wp-content/themes/masterworks-academy/` |
| Plugin files | `/opt/bitnami/wordpress/wp-content/plugins/mw-*/` |
| wp-config.php | `/opt/bitnami/wordpress/wp-config.php` |
| Apache config | `/opt/bitnami/apache/conf/` |
| Lightsail console | `lightsail.aws.amazon.com` |
| CloudFront console | `console.aws.amazon.com/cloudfront/` |
| GoDaddy DNS | `dcc.godaddy.com/manage-dns` |
| Sitemap | `masterworks.com/academy/sitemap_index.xml` |
| REST API | `masterworks.com/academy/wp-json/mw-academy/v1/` |

---

## Monthly Costs

| Resource | Cost |
|----------|------|
| Lightsail instance (4GB) | $20/month |
| Lightsail database (Standard) | $15/month |
| CloudFront | ~$5-20/month (based on traffic) |
| ACF Pro license | ~$4/month ($49/year) |
| WP Rocket license | ~$5/month ($59/year, optional) |
| **Total** | **~$45-65/month** |
