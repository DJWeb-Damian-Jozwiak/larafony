# Nginx Configuration for Larafony

This guide explains how to configure Nginx to serve a Larafony application.

## Requirements

- Nginx 1.18 or higher
- PHP 8.5 with PHP-FPM
- PHP 8.5 extensions as required by Larafony

## Basic Server Block Configuration

Create a new configuration file (e.g., `/etc/nginx/sites-available/larafony`):

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name larafony.local www.larafony.local;
    root /var/www/larafony/public;

    index index.php index.html;

    charset utf-8;

    # Logging
    access_log /var/log/nginx/larafony-access.log;
    error_log /var/log/nginx/larafony-error.log;

    # Serve static files directly
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /(composer\.json|composer\.lock|package\.json|package-lock\.json)$ {
        deny all;
    }
}
```

## SSL/HTTPS Configuration

### Using Let's Encrypt (Certbot)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d larafony.local -d www.larafony.local
```

### Manual SSL Configuration

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name larafony.local www.larafony.local;
    root /var/www/larafony/public;

    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/larafony.crt;
    ssl_certificate_key /etc/ssl/private/larafony.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # HSTS (optional but recommended)
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    charset utf-8;

    access_log /var/log/nginx/larafony-ssl-access.log;
    error_log /var/log/nginx/larafony-ssl-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\. {
        deny all;
    }

    location ~ /(composer\.json|composer\.lock|package\.json|package-lock\.json)$ {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;

    server_name larafony.local www.larafony.local;

    return 301 https://$server_name$request_uri;
}
```

## Production-Optimized Configuration

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name larafony.local www.larafony.local;
    root /var/www/larafony/public;

    index index.php;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/larafony.crt;
    ssl_certificate_key /etc/ssl/private/larafony.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    charset utf-8;

    # Logging
    access_log /var/log/nginx/larafony-access.log;
    error_log /var/log/nginx/larafony-error.log;

    # Increase upload size
    client_max_body_size 50M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP-FPM configuration with optimizations
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # FastCGI optimizations
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
        fastcgi_read_timeout 240;

        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Deny access to sensitive files
    location ~ /(composer\.json|composer\.lock|package\.json|package-lock\.json|\.env)$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Favicon handling
    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    # Robots.txt handling
    location = /robots.txt {
        access_log off;
        log_not_found off;
    }
}
```

## Enable the Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/larafony /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

## Add to Hosts File (for local development)

```bash
sudo nano /etc/hosts
```

Add:
```
127.0.0.1    larafony.local
```

## PHP-FPM Configuration

### Optimize PHP-FPM Pool

Edit `/etc/php/8.5/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data
listen = /run/php/php8.5-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process manager settings
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Status page
pm.status_path = /fpm-status

; Slow log
slowlog = /var/log/php8.5-fpm-slow.log
request_slowlog_timeout = 5s
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.5-fpm
```

## Permissions

Set proper permissions:

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/larafony

# Set directory permissions
sudo find /var/www/larafony -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/larafony -type f -exec chmod 644 {} \;
```

## Troubleshooting

### 502 Bad Gateway

1. Check PHP-FPM status:
   ```bash
   sudo systemctl status php8.5-fpm
   ```

2. Verify socket path in both Nginx config and PHP-FPM pool:
   ```bash
   ls -la /run/php/php8.5-fpm.sock
   ```

3. Check PHP-FPM error log:
   ```bash
   sudo tail -f /var/log/php8.5-fpm.log
   ```

### 404 Not Found

Verify the `root` directive points to the `public/` directory:
```nginx
root /var/www/larafony/public;
```

### Permission Denied

1. Check Nginx error log:
   ```bash
   sudo tail -f /var/log/nginx/larafony-error.log
   ```

2. Verify ownership and permissions:
   ```bash
   ls -la /var/www/larafony
   ```

## Performance Monitoring

### Enable Nginx Status Page

Add to server block:
```nginx
location /nginx-status {
    stub_status on;
    access_log off;
    allow 127.0.0.1;
    deny all;
}
```

Check status:
```bash
curl http://localhost/nginx-status
```

### Enable PHP-FPM Status Page

Add to server block:
```nginx
location ~ ^/(fpm-status|fpm-ping)$ {
    fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    allow 127.0.0.1;
    deny all;
}
```

Check status:
```bash
curl http://localhost/fpm-status
```

## Additional Optimizations

### Enable HTTP/2 Push (optional)

```nginx
location = /index.php {
    http2_push /css/app.css;
    http2_push /js/app.js;
    # ... rest of PHP configuration
}
```

### Rate Limiting

Add to `http` block in `/etc/nginx/nginx.conf`:
```nginx
limit_req_zone $binary_remote_addr zone=one:10m rate=10r/s;
```

Add to server block:
```nginx
location / {
    limit_req zone=one burst=20;
    try_files $uri $uri/ /index.php?$query_string;
}
```
