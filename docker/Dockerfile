# Pinned to an exact patch release: `docker compose up --build` reuses a cached
# base image and never pulls a newer one, so a floating tag like 8.4-apache could
# silently stay on an old, vulnerable build. 8.4.27 fixes php2shell
# (CVE-2026-109618, CVE-2026-109619). Bump the version for future PHP security releases.
FROM php:8.4.27-apache-trixie

# Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Add build arguments
ARG DOMAIN
ARG GIT_VERSION

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    zip \
    unzip \
    git \
    default-mysql-client \
    openssl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql

# Upload progress is unused here and was the main php2shell attack path; the PHP
# advisory recommends keeping it off even on patched versions. expose_php would
# advertise the exact PHP version in an X-Powered-By header.
RUN printf 'session.upload_progress.enabled = Off\nexpose_php = Off\n' > "$PHP_INI_DIR/conf.d/zz-hardening.ini"

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

# Set environment variable from build arg
ENV GIT_VERSION=${GIT_VERSION:-N/A}
RUN echo "Setting GIT_VERSION to: $GIT_VERSION"

# Copy Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache-ssl.conf /etc/apache2/sites-available/default-ssl.conf

# Install PHP dependencies (cached layer — only re-runs when composer.json/lock changes)
# composer.json lives in src/ so it's available via both COPY src/ and the dev volume mount
COPY src/composer.json src/composer.lock* /var/www/html/
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Copy application code
COPY src/ ./
COPY database/ ./database/

# Create startup script
COPY docker/startup.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/startup.sh

# Set permissions for mounted volumes
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Pass environment variables to Apache
RUN echo "PassEnv APP_ENV" >> /etc/apache2/conf-available/environment.conf
RUN echo "PassEnv DOMAIN" >> /etc/apache2/conf-available/environment.conf
RUN echo "PassEnv GIT_VERSION" >> /etc/apache2/conf-available/environment.conf
RUN a2enconf environment
RUN a2ensite 000-default

# Set global ServerName
RUN echo "ServerName ${DOMAIN:-localhost}" >> /etc/apache2/apache2.conf

# Expose ports
EXPOSE 80
EXPOSE 443

# Start Apache and run migrations
CMD ["/usr/local/bin/startup.sh"]
