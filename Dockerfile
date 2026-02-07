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

# Create directories FIRST before copying files
RUN mkdir -p cache backups logs \
    && chmod 777 cache backups logs \
    && touch error.log \
    && chmod 666 error.log

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 777 cache backups logs \
    && chmod 666 error.log

# Create CSV and JSON files if they don't exist
RUN if [ ! -f movies.csv ]; then echo "movie_name,message_id,channel_id" > movies.csv; fi \
    && if [ ! -f users.json ]; then echo '{"users": {}, "total_requests": 0, "message_logs": []}' > users.json; fi \
    && if [ ! -f bot_stats.json ]; then echo '{"total_movies": 0, "total_users": 0, "total_searches": 0, "last_updated": "'$(date -Iseconds)'"}' > bot_stats.json; fi \
    && chmod 666 movies.csv users.json bot_stats.json

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]
