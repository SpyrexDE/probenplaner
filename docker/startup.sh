#!/bin/bash

# Create necessary directories
mkdir -p /var/www/html/database/migrations

# Function to test MySQL connection
wait_for_mysql() {
    echo "Waiting for MySQL to be ready..."
    while ! mysqladmin ping -h"db" -u"probenplaner" -p"kDo1#a43" --silent; do
        sleep 1
    done
    echo "MySQL is ready!"
}

# Wait for MySQL to be ready
wait_for_mysql

# Run migrations
echo "Running database migrations..."
cd /var/www/html/database && php run_migrations.php

# Start Apache
echo "Starting Apache..."
apache2-foreground 