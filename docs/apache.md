# Apache Configuration for Larafony

This guide explains how to configure Apache to serve a Larafony application.

## Requirements

- Apache 2.4 or higher
- PHP 8.5 with Apache module (mod_php) or PHP-FPM
- mod_rewrite enabled

## Enable Required Modules

```bash
sudo a2enmod rewrite
sudo a2enmod php8.5  # if using mod_php
sudo systemctl restart apache2
```

## Virtual Host Configuration

### Basic Configuration

Create a new virtual host file (e.g., `/etc/apache2/sites-available/larafony.conf`):

```apache
<VirtualHost *:80>
    ServerName larafony.local
    ServerAlias www.larafony.local

    DocumentRoot /var/www/larafony/public

    <Directory /var/www/larafony/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/larafony-error.log
    CustomLog ${APACHE_LOG_DIR}/larafony-access.log combined
</VirtualHost>
```

### With PHP-FPM

If using PHP-FPM instead of mod_php:

```apache
<VirtualHost *:80>
    ServerName larafony.local
    ServerAlias www.larafony.local

    DocumentRoot /var/www/larafony/public

    <Directory /var/www/larafony/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/larafony-error.log
    CustomLog ${APACHE_LOG_DIR}/larafony-access.log combined
</VirtualHost>
```

For PHP-FPM, ensure these modules are enabled:
```bash
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.5-fpm
```

### SSL/HTTPS Configuration

```apache
<VirtualHost *:443>
    ServerName larafony.local
    ServerAlias www.larafony.local

    DocumentRoot /var/www/larafony/public

    <Directory /var/www/larafony/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/ca-bundle.crt

    ErrorLog ${APACHE_LOG_DIR}/larafony-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/larafony-ssl-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName larafony.local
    ServerAlias www.larafony.local

    Redirect permanent / https://larafony.local/
</VirtualHost>
```

Enable SSL module:
```bash
sudo a2enmod ssl
```

## .htaccess File

The `.htaccess` file in the `public/` directory handles URL rewriting:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## Enable the Site

```bash
# Enable the site
sudo a2ensite larafony.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

## Add to Hosts File (for local development)

```bash
sudo nano /etc/hosts
```

Add:
```
127.0.0.1    larafony.local
```

## Permissions

Set proper permissions for the application:

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/larafony

# Set directory permissions
sudo find /var/www/larafony -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/larafony -type f -exec chmod 644 {} \;
```

## Troubleshooting

### 500 Internal Server Error

1. Check Apache error logs:
   ```bash
   sudo tail -f /var/log/apache2/larafony-error.log
   ```

2. Verify mod_rewrite is enabled:
   ```bash
   apache2ctl -M | grep rewrite
   ```

3. Ensure `AllowOverride All` is set in the virtual host configuration

### Permission Denied Errors

Check file permissions and ownership:
```bash
ls -la /var/www/larafony
```

Ensure Apache user (typically `www-data`) has access to the files.

### PHP Not Executing

1. Verify PHP module is loaded:
   ```bash
   apache2ctl -M | grep php
   ```

2. Check PHP-FPM status (if using PHP-FPM):
   ```bash
   sudo systemctl status php8.5-fpm
   ```

## Performance Optimization

### Enable OPcache

In your `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### Enable Apache MPM Event

```bash
sudo a2dismod mpm_prefork
sudo a2enmod mpm_event
sudo systemctl restart apache2
```

### Enable Compression

```bash
sudo a2enmod deflate
sudo systemctl restart apache2
```

Add to virtual host or .htaccess:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```
