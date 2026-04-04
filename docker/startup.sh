#!/bin/bash

# Create necessary directories
mkdir -p /var/www/html/database/migrations

# Install Composer dependencies if vendor/ was wiped by a dev volume mount
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    cd /var/www/html && composer install --no-dev --no-interaction --optimize-autoloader --quiet
fi

# Check SSL certificates only for test and production
if [ "${APP_ENV}" = "test" ] || [ "${APP_ENV}" = "production" ]; then
    if [ ! -f /etc/apache2/ssl/crt.pem ] || [ ! -f /etc/apache2/ssl/key.pem ]; then
        echo "Error: SSL certificates not found in /etc/apache2/ssl/"
        echo "Please make sure CERT_PATH is set correctly in your .env file"
        echo "Expected files: crt.pem, key.pem"
        exit 1
    fi
    a2ensite default-ssl >/dev/null 2>&1 || true
else
    echo "Skipping SSL certificate check in ${APP_ENV:-development} environment"
    a2dissite default-ssl >/dev/null 2>&1 || true
fi

# Function to test MySQL connection
wait_for_mysql() {
    echo "Waiting for MySQL to be ready..."
    DB_USER=${DB_USER:-probenplaner}
    if [ -z "$DB_PASSWORD" ]; then
        echo "Error: DB_PASSWORD environment variable is not set"
        exit 1
    fi
    
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