#!/bin/bash

# SSL Setup Script for Dokterku Healthcare System
# This script helps set up SSL certificates using Let's Encrypt

set -e

DOMAIN=""
EMAIL=""
WEBROOT="/var/www/html/dokterku/public"

# Function to display usage
usage() {
    echo "Usage: $0 -d domain.com -e email@domain.com [-w /path/to/webroot]"
    echo "  -d: Your domain name (required)"
    echo "  -e: Your email address (required)"
    echo "  -w: Webroot path (optional, default: /var/www/html/dokterku/public)"
    exit 1
}

# Parse command line arguments
while getopts "d:e:w:h" opt; do
    case $opt in
        d) DOMAIN="$OPTARG";;
        e) EMAIL="$OPTARG";;
        w) WEBROOT="$OPTARG";;
        h) usage;;
        *) usage;;
    esac
done

# Check required parameters
if [ -z "$DOMAIN" ] || [ -z "$EMAIL" ]; then
    echo "❌ Error: Domain and email are required"
    usage
fi

echo "🔒 Setting up SSL for $DOMAIN"
echo "📧 Email: $EMAIL"
echo "📁 Webroot: $WEBROOT"

# Check if certbot is installed
if ! command -v certbot &> /dev/null; then
    echo "📦 Installing certbot..."
    # Ubuntu/Debian
    if command -v apt-get &> /dev/null; then
        sudo apt-get update
        sudo apt-get install -y certbot python3-certbot-nginx
    # CentOS/RHEL
    elif command -v yum &> /dev/null; then
        sudo yum install -y certbot python3-certbot-nginx
    else
        echo "❌ Error: Please install certbot manually"
        exit 1
    fi
fi

# Create nginx configuration for the domain
echo "🌐 Creating nginx configuration..."
sudo tee "/etc/nginx/sites-available/$DOMAIN" > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $WEBROOT;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable the site
sudo ln -sf "/etc/nginx/sites-available/$DOMAIN" "/etc/nginx/sites-enabled/"

# Test nginx configuration
echo "🔧 Testing nginx configuration..."
sudo nginx -t

# Reload nginx
sudo systemctl reload nginx

# Obtain SSL certificate
echo "🔒 Obtaining SSL certificate..."
sudo certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --email "$EMAIL" --agree-tos --no-eff-email

# Set up automatic renewal
echo "⏰ Setting up automatic renewal..."
sudo crontab -l | { cat; echo "0 12 * * * /usr/bin/certbot renew --quiet"; } | sudo crontab -

echo "✅ SSL setup completed!"
echo "🌐 Your site should now be accessible at https://$DOMAIN"
echo "🔄 Certificate will auto-renew via cron job"