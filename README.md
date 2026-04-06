# mw-academy

WordPress-based content platform served at `masterworks.com/academy` via reverse proxy from a dedicated AWS Lightsail instance. Includes the content production pipeline (AI-assisted research and writing skills), brand assets, and sample content.

## Repository Structure

```
mw-academy/
├── assets/
│   ├── images/                    # Reference artwork images
│   └── logos/                     # Masterworks brand logos
├── config/
│   ├── .htaccess                  # WordPress rewrite rules for /academy
│   ├── nginx-reverse-proxy.conf   # Nginx config (alt to CloudFront)
│   ├── robots-additions.txt       # Lines to merge into robots.txt
│   └── wp-config-additions.php    # PHP constants for wp-config.php
├── content/
│   ├── drafts/                    # Article drafts (skill output)
│   ├── reports/                   # Art investing report .docx files
│   ├── sample-articles/           # Artist dossiers and insights
│   └── social/                    # Instagram carousel examples
├── infrastructure/
│   ├── cloudformation.yaml        # AWS Lightsail, static IP, DB, CloudFront
│   └── setup-lightsail.sh         # Post-provisioning instance setup
├── plugins/
│   ├── mw-content-types/          # Custom post types and taxonomies
│   ├── mw-contentful-integration/ # Contentful CMS integration
│   ├── mw-data-visualizations/    # Chart and data viz blocks
│   └── mw-seo-schema/            # SEO schema markup
├── skills/
│   └── mw-academy-writer/         # Claude skill for article research + writing
│       └── SKILL.md
├── wordpress-theme/
│   └── masterworks-academy/       # Custom WordPress theme
├── .env.example                   # Environment variable template
├── docker-compose.yml
├── setup-local.sh
├── SETUP-GUIDE.md
└── README.md
```

## Setup

1. Copy `.env.example` to `.env` and fill in your API keys
2. See `SETUP-GUIDE.md` for full WordPress deployment instructions

## Content Production

The `skills/mw-academy-writer/` skill is a Claude Code agent that researches and writes publication-ready articles for Masterworks Academy. It uses Perplexity API for deep research and outputs SEO/AEO-optimized markdown to `content/drafts/`. See the skill's `SKILL.md` for full documentation.

## Architecture

```
                         Internet
                            |
                     +------+------+
                     |  Route 53   |
                     |  DNS CNAME  |
                     +------+------+
                            |
                     +------+------+
                     | CloudFront  |
                     | Distribution|
                     +------+------+
                       /         \
                      /           \
            Default Origin    /academy/* Origin
                 |                    |
         +-------+-------+   +-------+-------+
         |  React App    |   |   Lightsail   |
         |  (S3 / EC2 /  |   |   WordPress   |
         |   Vercel)     |   |   Instance    |
         +---------------+   +-------+-------+
                                     |
                              +------+------+
                              |  Lightsail  |
                              |  MySQL DB   |
                              +-------------+
```

## Prerequisites

- AWS account with permissions for Lightsail, CloudFront, ACM, and IAM
- AWS CLI v2 installed and configured
- ACM certificate for `masterworks.com` in `us-east-1` (required by CloudFront)
- SSH key pair for Lightsail instance access
- Domain DNS managed via Route 53 (or ability to add CNAME records)
- Custom WordPress theme packaged as a `.zip` file
- Premium plugin licenses: ACF Pro, WP Rocket (optional)

## Quick Start (from zero)

### Step 1: Deploy the CloudFormation Stack

```bash
aws cloudformation deploy \
  --template-file infrastructure/cloudformation.yaml \
  --stack-name masterworks-academy-production \
  --parameter-overrides \
    ReactAppOriginDomain=your-react-app.example.com \
    AcmCertificateArn=arn:aws:acm:us-east-1:123456789012:certificate/abc-123 \
    DatabaseMasterPassword=YOUR_STRONG_PASSWORD \
    EnvironmentTag=production \
  --tags Project=masterworks-academy Environment=production
```

Wait for the stack to reach `CREATE_COMPLETE` (approximately 10-15 minutes).

### Step 2: Get the Lightsail Instance IP

```bash
aws cloudformation describe-stacks \
  --stack-name masterworks-academy-production \
  --query 'Stacks[0].Outputs' \
  --output table
```

Note the `LightsailStaticIp` and `CloudFrontDomainName`.

### Step 3: SSH into Lightsail and Run Setup

```bash
# Get the default Bitnami password
ssh -i ~/.ssh/lightsail-key.pem bitnami@<LIGHTSAIL_IP>
cat /home/bitnami/bitnami_credentials

# Upload and run the setup script
scp -i ~/.ssh/lightsail-key.pem infrastructure/setup-lightsail.sh bitnami@<LIGHTSAIL_IP>:/tmp/
ssh -i ~/.ssh/lightsail-key.pem bitnami@<LIGHTSAIL_IP>
sudo bash /tmp/setup-lightsail.sh
```

### Step 4: Apply Configuration Files

```bash
# Upload wp-config additions (merge manually into wp-config.php)
scp -i ~/.ssh/lightsail-key.pem config/wp-config-additions.php bitnami@<LIGHTSAIL_IP>:/tmp/

# Upload .htaccess
scp -i ~/.ssh/lightsail-key.pem config/.htaccess bitnami@<LIGHTSAIL_IP>:/opt/bitnami/wordpress/.htaccess
```

On the instance, edit `/opt/bitnami/wordpress/wp-config.php` and paste the contents of `wp-config-additions.php` above the "stop editing" line. Replace all `{{PLACEHOLDER}}` values.

### Step 5: Update DNS

Point `masterworks.com` to the CloudFront distribution domain name:

```
masterworks.com  CNAME  d1234abcdef.cloudfront.net
```

Or if using Route 53, create an alias record pointing to the CloudFront distribution.

### Step 6: Verify

```bash
curl -I https://masterworks.com/academy/
# Should return HTTP 200 with WordPress headers

curl -I https://masterworks.com/
# Should return the React app
```

## Deploying to Staging

1. Deploy a separate CloudFormation stack with `EnvironmentTag=staging`:

```bash
aws cloudformation deploy \
  --template-file infrastructure/cloudformation.yaml \
  --stack-name masterworks-academy-staging \
  --parameter-overrides \
    ReactAppOriginDomain=staging-react.example.com \
    AcmCertificateArn=arn:aws:acm:us-east-1:123456789012:certificate/staging-cert \
    DatabaseMasterPassword=STAGING_PASSWORD \
    EnvironmentTag=staging
```

2. Set `WP_ENVIRONMENT_TYPE` to `staging` in wp-config.php (enables debug logging).
3. Use a separate subdomain (e.g., `staging.masterworks.com`) or access via the CloudFront domain directly.

## Deploying to Production

1. Merge and test all changes on staging first.
2. SSH into the production Lightsail instance and pull updates:

```bash
# Update plugins
sudo -u bitnami wp plugin update --all --path=/opt/bitnami/wordpress

# Clear caches
sudo -u bitnami wp cache flush --path=/opt/bitnami/wordpress

# If using WP Rocket:
sudo -u bitnami wp rocket clean --confirm --path=/opt/bitnami/wordpress
```

3. Invalidate CloudFront cache if needed:

```bash
aws cloudfront create-invalidation \
  --distribution-id <DISTRIBUTION_ID> \
  --paths "/academy/*"
```

## Plugin Dependencies

| Plugin | Type | Purpose |
|--------|------|---------|
| Yoast SEO | Free | SEO meta, sitemaps, schema markup |
| Wordfence | Free | Firewall, malware scanning, login security |
| Redirection | Free | 301 redirect management |
| Safe SVG | Free | Allow SVG uploads safely |
| WP Mail SMTP | Free | Reliable email delivery via SES/SMTP |
| TablePress | Free | Data tables in posts/pages |
| Code Snippets | Free | Custom PHP snippets without theme editing |
| Advanced Custom Fields PRO | Premium | Custom fields, flexible content, options pages |
| WP Rocket | Premium | Page caching, minification, CDN integration |

## Configuration Reference

### CloudFormation Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `ReactAppOriginDomain` | (required) | Domain of the existing React app |
| `AcmCertificateArn` | (required) | ACM certificate ARN in us-east-1 |
| `InstanceBlueprintId` | `wordpress` | Lightsail blueprint |
| `InstanceBundleId` | `medium_3_0` | Instance plan (~$20/mo) |
| `DatabaseBundleId` | `medium_2_0` | Database plan (~$30/mo) |
| `DatabaseMasterUsername` | `dbmasteruser` | MySQL master username |
| `DatabaseMasterPassword` | (required) | MySQL master password |
| `EnvironmentTag` | `production` | `production` or `staging` |

### wp-config.php Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{DB_NAME}}` | Database name (e.g., `academy_wp`) |
| `{{DB_USER}}` | Database username |
| `{{DB_PASSWORD}}` | Database password |
| `{{DB_HOST}}` | Lightsail DB endpoint |
| `{{ENVIRONMENT}}` | `production` or `staging` |
| `{{REDIS_HOST}}` | ElastiCache endpoint (future) |
| `{{AUTH_KEY}}` etc. | Security salts from WordPress.org API |

### Nginx Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{LIGHTSAIL_IP}}` | Static IP from CloudFormation output |
| `{{SSL_CERT_PATH}}` | Path to SSL certificate file |
| `{{SSL_KEY_PATH}}` | Path to SSL private key file |

### Lightsail Setup Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{WP_ADMIN_USER}}` | WordPress admin username |
| `{{WP_ADMIN_EMAIL}}` | WordPress admin email |
| `{{WP_ADMIN_PASSWORD}}` | WordPress admin password |
| `{{DB_HOST}}` | Lightsail DB endpoint |
| `{{DB_NAME}}` | Database name |
| `{{DB_USER}}` | Database username |
| `{{DB_PASSWORD}}` | Database password |
| `{{THEME_ZIP_URL}}` | URL to custom theme zip |

## Useful Commands

```bash
# SSH into Lightsail
ssh -i ~/.ssh/lightsail-key.pem bitnami@<IP>

# WP-CLI on Lightsail
sudo -u bitnami wp --path=/opt/bitnami/wordpress <command>

# Check WordPress status
sudo -u bitnami wp option get siteurl --path=/opt/bitnami/wordpress

# Invalidate CloudFront cache
aws cloudfront create-invalidation --distribution-id <ID> --paths "/academy/*"

# View CloudFormation stack outputs
aws cloudformation describe-stacks --stack-name masterworks-academy-production --query 'Stacks[0].Outputs'

# Tail WordPress debug log (staging only)
tail -f /opt/bitnami/wordpress/wp-content/debug.log
```
