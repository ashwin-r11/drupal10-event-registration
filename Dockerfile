# Custom Dockerfile for Drupal 10 with PHP 8.2
#
# This Dockerfile extends the official Drupal 10 image with:
# - Drush CLI tool for site management
# - Development-friendly PHP settings
# - Proper permissions for Drupal files

FROM drupal:10-php8.2-apache

# Install additional PHP extensions and utilities
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /opt/drupal

# Install Drush globally via Composer
RUN composer require drush/drush:^12

# Configure PHP for development
RUN { \
    echo 'memory_limit = 512M'; \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'max_execution_time = 300'; \
    echo 'display_errors = On'; \
    echo 'error_reporting = E_ALL'; \
} > /usr/local/etc/php/conf.d/drupal-dev.ini

# Enable Apache mod_rewrite (already enabled in base, but ensure it)
RUN a2enmod rewrite

# Create directory for custom modules
RUN mkdir -p /opt/drupal/web/modules/custom

# Set proper permissions
RUN chown -R www-data:www-data /opt/drupal/web/sites/default \
    && chmod -R 755 /opt/drupal/web/modules/custom

# Add Drush to PATH
ENV PATH="${PATH}:/opt/drupal/vendor/bin"

# Expose port 80
EXPOSE 80

# Default command
CMD ["apache2-foreground"]
