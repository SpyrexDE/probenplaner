#!/bin/bash

# Create necessary directories
mkdir -p /var/www/html/database/migrations

# Verify SSL certificates exist
if [ ! -f /etc/apache2/ssl/cert.pem ] || [ ! -f /etc/apache2/ssl/privkey.pem ] || [ ! -f /etc/apache2/ssl/chain.pem ]; then
    echo "Generating SSL certificates..."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/apache2/ssl/privkey.pem \
        -out /etc/apache2/ssl/cert.pem \
        -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost" \
        && cp /etc/apache2/ssl/cert.pem /etc/apache2/ssl/chain.pem \
        && chmod 644 /etc/apache2/ssl/*.pem \
        && chown www-data:www-data /etc/apache2/ssl/*.pem
fi

# Function to test MySQL connection
wait_for_mysql() {
    echo "Waiting for MySQL to be ready..."
    DB_USER=${DB_USER:-probenplaner}
    DB_PASSWORD=${DB_PASSWORD:-kDo1#a43}
    
    while ! mysqladmin ping -h"db" -u"$DB_USER" -p"$DB_PASSWORD" --silent; do
        sleep 1
    done
    echo "MySQL is ready!"
}

# Wait for MySQL to be ready
wait_for_mysql

# Run migrations
echo "Running database migrations..."
cd /var/www/html/database

# Show current migration status
php cli-migrate.php status

# Run pending migrations
if ! php cli-migrate.php up; then
    echo "Migration failed! Please check the logs and fix any issues."
    exit 1
fi

echo "Migrations completed successfully!"

# Start Apache
echo "Starting Apache..."
apache2-foreground 