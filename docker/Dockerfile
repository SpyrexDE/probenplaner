FROM php:8.0-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    default-mysql-client \
    openssl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod ssl
RUN a2enmod env
RUN a2enmod setenvif

# Create SSL directory
RUN mkdir -p /etc/apache2/ssl

# Set working directory
WORKDIR /var/www/html

# Copy Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Create startup script
COPY docker/startup.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/startup.sh

# Set permissions for mounted volumes
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Pass environment variables to Apache
RUN echo "PassEnv APP_ENV" >> /etc/apache2/conf-available/environment.conf
RUN a2enconf environment

# Expose ports
EXPOSE 80
EXPOSE 443

# Start Apache and run migrations
CMD ["/usr/local/bin/startup.sh"] 