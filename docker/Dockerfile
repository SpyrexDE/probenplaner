FROM php:8.0-apache

# --- STOPGAP: Debian 11 "bullseye" apt sources --------------------------------
# php:8.0-apache is built on Debian bullseye, which is end-of-life. Packages from
# bullseye-security are no longer reliably served by deb.debian.org (404s), so
# uncached image builds (e.g. prod deploys) fail at "apt-get install".
# Workaround: take bullseye-security from a frozen snapshot.debian.org timestamp.
# The snapshot's Release file is past its Valid-Until date, so apt must not
# reject it as expired. The grep makes the build fail loudly if the base image's
# sources.list no longer has the expected line (instead of silently skipping).
# TODO: move to a supported base image (PHP >= 8.2 on Debian bookworm/trixie)
#       and remove this block.
RUN sed -i 's#^deb http://deb.debian.org/debian-security bullseye-security main$#deb http://snapshot.debian.org/archive/debian-security/20260825T000000Z bullseye-security main#' /etc/apt/sources.list \
    && grep -q '^deb http://snapshot.debian.org/archive/debian-security/20260825T000000Z bullseye-security main$' /etc/apt/sources.list \
    && printf 'Acquire::Check-Valid-Until "false";\nAcquire::Retries "3";\n' > /etc/apt/apt.conf.d/99bullseye-snapshot
# ------------------------------------------------------------------------------

# Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Add build arguments
ARG DOMAIN
ARG GIT_VERSION

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
