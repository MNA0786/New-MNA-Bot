# Use official PHP image with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && a2enmod headers

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 777 /var/www/html/cache \
    && chmod 777 /var/www/html/backups \
    && chmod 777 /var/www/html/logs \
    && touch /var/www/html/error.log \
    && chmod 666 /var/www/html/error.log

# Create necessary directories
RUN mkdir -p cache backups logs \
    && chmod 777 cache backups logs

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]