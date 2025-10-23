# Deployment Guide - OAuth Sprinkle

Complete guide for deploying the OAuth Sprinkle to production environments.

## Pre-Deployment Checklist

Before deploying to production, ensure:

- [ ] OAuth providers configured for production URLs
- [ ] HTTPS enabled on production server
- [ ] Environment variables secured
- [ ] Database migrations tested
- [ ] All providers tested in staging
- [ ] Backup strategy in place
- [ ] Monitoring configured
- [ ] Documentation updated for team

---

## Step 1: Server Requirements

### Minimum Requirements

- **PHP**: 8.1 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **SSL Certificate**: Required for production
- **Composer**: Latest version

### Recommended PHP Extensions

```bash
php -m | grep -E 'pdo|openssl|mbstring|json|curl'
```

Required extensions:
- `pdo_mysql` - Database connectivity
- `openssl` - SSL/TLS support
- `mbstring` - String handling
- `json` - JSON parsing
- `curl` - HTTP requests
- `session` - Session management

---

## Step 2: Update OAuth Provider Settings

### Update Redirect URIs

For each OAuth provider, update redirect URIs from development to production:

#### Google
1. Go to https://console.cloud.google.com/
2. Select your project
3. Navigate to "Credentials"
4. Edit OAuth 2.0 Client ID
5. Update "Authorized redirect URIs":
   ```
   https://yourdomain.com/oauth/google/callback
   ```
6. Save changes

#### Facebook
1. Go to https://developers.facebook.com/
2. Select your app
3. Navigate to "Facebook Login" > "Settings"
4. Update "Valid OAuth Redirect URIs":
   ```
   https://yourdomain.com/oauth/facebook/callback
   ```
5. **Important**: Change app status from "Development" to "Live"

#### LinkedIn
1. Go to https://www.linkedin.com/developers/
2. Select your app
3. Navigate to "Auth" tab
4. Update "Redirect URLs":
   ```
   https://yourdomain.com/oauth/linkedin/callback
   ```
5. Save changes

#### Microsoft
1. Go to https://portal.azure.com/
2. Navigate to "Azure Active Directory" > "App registrations"
3. Select your app
4. Go to "Authentication"
5. Update "Redirect URIs":
   ```
   https://yourdomain.com/oauth/microsoft/callback
   ```
6. Save changes

---

## Step 3: Configure Production Environment

### Environment Variables

Create or update `.env` file on production server:

```env
# Production mode
UF_MODE=production

# Site URL
APP_URL=https://yourdomain.com

# Database (production)
DB_HOST=your-db-host
DB_NAME=your-db-name
DB_USER=your-db-user
DB_PASSWORD=secure-password

# OAuth Credentials (production)
OAUTH_GOOGLE_CLIENT_ID=your-production-google-id
OAUTH_GOOGLE_CLIENT_SECRET=your-production-google-secret

OAUTH_FACEBOOK_CLIENT_ID=your-production-facebook-id
OAUTH_FACEBOOK_CLIENT_SECRET=your-production-facebook-secret

OAUTH_LINKEDIN_CLIENT_ID=your-production-linkedin-id
OAUTH_LINKEDIN_CLIENT_SECRET=your-production-linkedin-secret

OAUTH_MICROSOFT_CLIENT_ID=your-production-microsoft-id
OAUTH_MICROSOFT_CLIENT_SECRET=your-production-microsoft-secret

# Session security
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=lax
```

### Secure File Permissions

```bash
# Set proper ownership
chown -R www-data:www-data /path/to/app

# Protect .env file
chmod 600 .env

# Writable directories
chmod 775 app/logs app/cache app/sessions
```

---

## Step 4: Deploy Application

### Using Git Deployment

```bash
# SSH to production server
ssh user@yourdomain.com

# Navigate to app directory
cd /var/www/yourdomain.com

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php bakery migrate

# Clear cache
php bakery clear-cache

# Restart services
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx
```

### Using Deployment Tools

#### Deployer Example

```php
// deploy.php
namespace Deployer;

require 'recipe/common.php';

// Configuration
set('repository', 'git@github.com:yourusername/your-app.git');
set('shared_files', ['.env']);
set('writable_dirs', ['app/logs', 'app/cache', 'app/sessions']);

// Hosts
host('production')
    ->hostname('yourdomain.com')
    ->user('deployer')
    ->set('deploy_path', '/var/www/yourdomain.com');

// Tasks
task('bakery:migrate', function() {
    run('cd {{release_path}} && php bakery migrate');
});

task('bakery:cache', function() {
    run('cd {{release_path}} && php bakery clear-cache');
});

// Deploy flow
after('deploy:vendors', 'bakery:migrate');
after('deploy:vendors', 'bakery:cache');
```

---

## Step 5: Web Server Configuration

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Root directory
    root /var/www/yourdomain.com/public;
    index index.php;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # PHP processing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/yourdomain.com-access.log;
    error_log /var/log/nginx/yourdomain.com-error.log;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

### Apache Configuration

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/yourdomain.com/public

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/key.pem

    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    <Directory /var/www/yourdomain.com/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/yourdomain.com-error.log
    CustomLog ${APACHE_LOG_DIR}/yourdomain.com-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

---

## Step 6: Database Setup

### Run Migrations

```bash
php bakery migrate
```

### Verify Tables

```sql
SHOW TABLES LIKE 'oauth_connections';
DESCRIBE oauth_connections;
```

### Database Optimization

```sql
-- Add indexes if needed
ALTER TABLE oauth_connections ADD INDEX idx_user_provider (user_id, provider);

-- Analyze tables
ANALYZE TABLE oauth_connections;
```

---

## Step 7: Testing in Production

### Smoke Tests

```bash
# Test 1: Check if site loads
curl -I https://yourdomain.com

# Test 2: Check OAuth login page
curl https://yourdomain.com/oauth/login

# Test 3: Check database connection
php bakery debug:database

# Test 4: Check cache
php bakery debug:cache
```

### Manual OAuth Testing

1. **Test Google OAuth**:
   - Navigate to `https://yourdomain.com/oauth/login`
   - Click "Sign in with Google"
   - Complete OAuth flow
   - Verify user created and logged in

2. **Test Additional Providers**:
   - Repeat for Facebook, LinkedIn, Microsoft
   - Test with fresh email addresses
   - Verify all data saved correctly

3. **Test Multiple Connections**:
   - Log in with one provider
   - Go to settings
   - Link another provider
   - Log out and test logging in with both

4. **Test Error Handling**:
   - Cancel OAuth authorization
   - Use invalid credentials
   - Test with expired session
   - Verify proper error messages

---

## Step 8: Monitoring & Logging

### Application Logs

Monitor OAuth-related logs:

```bash
# Watch logs in real-time
tail -f /var/www/yourdomain.com/app/logs/userfrosting.log

# Search for OAuth errors
grep -i "oauth" /var/www/yourdomain.com/app/logs/userfrosting.log
```

### Web Server Logs

```bash
# Nginx access logs
tail -f /var/log/nginx/yourdomain.com-access.log | grep oauth

# Nginx error logs
tail -f /var/log/nginx/yourdomain.com-error.log
```

### Database Monitoring

```sql
-- Monitor OAuth connections
SELECT 
    provider,
    COUNT(*) as total_connections,
    COUNT(DISTINCT user_id) as unique_users
FROM oauth_connections
GROUP BY provider;

-- Recent OAuth logins
SELECT 
    u.email,
    oc.provider,
    oc.updated_at as last_login
FROM oauth_connections oc
JOIN users u ON u.id = oc.user_id
ORDER BY oc.updated_at DESC
LIMIT 20;
```

---

## Step 9: Security Hardening

### SSL/TLS Configuration

```bash
# Test SSL configuration
openssl s_client -connect yourdomain.com:443

# Check SSL rating
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=yourdomain.com
```

### Rate Limiting

Configure rate limiting for OAuth endpoints:

```nginx
# Nginx rate limiting
limit_req_zone $binary_remote_addr zone=oauth:10m rate=10r/m;

location ~ ^/oauth {
    limit_req zone=oauth burst=5 nodelay;
    # ... rest of config
}
```

### Firewall Rules

```bash
# Allow only necessary ports
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP (for redirect)
ufw allow 443/tcp  # HTTPS
ufw enable
```

---

## Step 10: Backup & Recovery

### Database Backups

```bash
# Automated daily backup
cat > /etc/cron.daily/backup-oauth << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u backup_user -p'password' database_name oauth_connections > \
    "$BACKUP_DIR/oauth_connections_$DATE.sql"
# Keep last 30 days
find "$BACKUP_DIR" -name "oauth_connections_*.sql" -mtime +30 -delete
EOF

chmod +x /etc/cron.daily/backup-oauth
```

### Configuration Backups

```bash
# Backup .env and configs
tar -czf /var/backups/app-config-$(date +%Y%m%d).tar.gz \
    /var/www/yourdomain.com/.env \
    /var/www/yourdomain.com/app/config/
```

---

## Troubleshooting Production Issues

### Issue: OAuth Redirect Not Working

**Symptoms**: Users get errors after OAuth authorization

**Solutions**:
1. Verify redirect URIs match exactly in provider settings
2. Check HTTPS is enabled
3. Verify DNS is correctly configured
4. Check web server logs for 404 errors

### Issue: Sessions Not Persisting

**Symptoms**: "Invalid OAuth state" errors

**Solutions**:
1. Check session directory permissions
2. Verify session configuration in php.ini
3. Ensure cookies are not being blocked
4. Check session storage path exists and is writable

### Issue: High Load on OAuth Login

**Symptoms**: Slow OAuth authentication

**Solutions**:
1. Enable Redis/Memcached for session storage
2. Optimize database queries
3. Enable HTTP/2
4. Use CDN for static assets
5. Monitor provider API rate limits

---

## Performance Optimization

### PHP OpCache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### Database Optimization

```sql
-- Add composite index
CREATE INDEX idx_user_provider_updated 
ON oauth_connections(user_id, provider, updated_at);

-- Optimize table
OPTIMIZE TABLE oauth_connections;
```

### Caching

```php
// config/cache.php
return [
    'cache' => [
        'driver' => 'redis',
        'host' => '127.0.0.1',
        'port' => 6379,
    ],
];
```

---

## Maintenance Tasks

### Weekly Tasks

- [ ] Review error logs
- [ ] Check OAuth connection statistics
- [ ] Verify backup success
- [ ] Monitor disk space

### Monthly Tasks

- [ ] Update dependencies: `composer update`
- [ ] Review OAuth provider changes
- [ ] Rotate logs
- [ ] Performance review

### Quarterly Tasks

- [ ] Rotate OAuth secrets
- [ ] Security audit
- [ ] Update SSL certificates (if needed)
- [ ] Review and optimize database

---

## Rollback Procedure

If issues occur after deployment:

```bash
# 1. Rollback code
git reset --hard PREVIOUS_COMMIT_SHA
composer install --no-dev

# 2. Rollback database (if needed)
php bakery migrate:rollback

# 3. Clear cache
php bakery clear-cache

# 4. Restart services
sudo systemctl reload php8.1-fpm nginx

# 5. Verify rollback
curl -I https://yourdomain.com
```

---

## Post-Deployment Checklist

After successful deployment:

- [ ] All OAuth providers tested
- [ ] Error logs checked
- [ ] Monitoring active
- [ ] Backups verified
- [ ] Team notified
- [ ] Documentation updated
- [ ] Performance baseline recorded

---

## Support & Resources

- **Documentation**: Review all `.md` files in repository
- **Logs**: `/var/www/yourdomain.com/app/logs/`
- **Status Page**: Consider setting up status monitoring
- **Alerts**: Configure alerts for errors and downtime

---

## Emergency Contacts

Document your emergency contacts:

- **Server Admin**: [Contact]
- **Database Admin**: [Contact]
- **OAuth Support**: GitHub Issues
- **Security Issues**: See SECURITY.md

---

Deployment complete! Monitor the application for the first 24-48 hours after deployment.
