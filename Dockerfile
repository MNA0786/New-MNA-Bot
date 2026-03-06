# ==================== ENTERTAINMENT TADKA BOT ====================
# Use official PHP image with Apache
FROM php:8.1-apache-bookworm

LABEL maintainer="Entertainment Tadka <admin@entertainment-tadka.com>"
LABEL version="4.0"
LABEL description="Telegram Bot for Movie Searches with Request System"

# Set working directory
WORKDIR /var/www/html

# ==================== SYSTEM DEPENDENCIES ====================
RUN apt-get update && apt-get install -y \
    # Build tools
    git \
    curl \
    wget \
    unzip \
    zip \
    # Image processing
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    # Encoding
    libonig-dev \
    libxml2-dev \
    # Archive support
    libzip-dev \
    # Other utilities
    nano \
    vim \
    cron \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mbstring \
        gd \
        pdo_mysql \
        mysqli \
        bcmath \
        zip \
        exif \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ==================== APACHE CONFIGURATION ====================
# Enable Apache modules
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires \
    && a2enmod deflate

# Configure Apache virtual host
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# ==================== PHP CONFIGURATION ====================
# Create PHP configuration files
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_vars = 3000" >> /usr/local/etc/php/conf.d/uploads.ini

# Session configuration
RUN echo "session.save_path = /tmp" > /usr/local/etc/php/conf.d/session.ini && \
    echo "session.gc_maxlifetime = 86400" >> /usr/local/etc/php/conf.d/session.ini && \
    echo "session.cookie_lifetime = 0" >> /usr/local/etc/php/conf.d/session.ini && \
    echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/session.ini && \
    echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/session.ini && \
    echo "session.cookie_secure = 1" >> /usr/local/etc/php/conf.d/session.ini && \
    echo "session.cookie_samesite = 'Strict'" >> /usr/local/etc/php/conf.d/session.ini

# Error reporting (production)
RUN echo "display_errors = 0" > /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_startup_errors = 0" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/errors.ini

# Timezone
RUN echo "date.timezone = Asia/Kolkata" > /usr/local/etc/php/conf.d/timezone.ini

# ==================== APPLICATION SETUP ====================
# Copy application files
COPY . .

# Create necessary directories
RUN mkdir -p backups cache logs tmp sessions data

# Set proper permissions
RUN chmod -R 755 /var/www/html && \
    chown -R www-data:www-data /var/www/html

# Set file permissions for writable files
RUN touch error.log movies.csv users.json bot_stats.json requests.json && \
    chmod 666 error.log movies.csv users.json bot_stats.json requests.json && \
    chmod 777 backups cache logs tmp sessions data

# Create empty index files if they don't exist
RUN touch cache/movie_index.json cache/metrics.json && \
    chmod 666 cache/movie_index.json cache/metrics.json

# ==================== SUPERVISOR CONFIGURATION ====================
# Copy supervisor configuration for background tasks
COPY docker/supervisor.conf /etc/supervisor/conf.d/bot.conf

# ==================== CRON JOBS ====================
# Setup cron for maintenance tasks
RUN echo "0 3 * * * cd /var/www/html && php -r 'require \"index.php\"; echo \"Maintenance run at \".date(\"Y-m-d H:i:s\").\"\\n\";' >> /var/log/cron.log 2>&1" > /etc/cron.d/bot-cron && \
    chmod 0644 /etc/cron.d/bot-cron && \
    crontab /etc/cron.d/bot-cron

# ==================== HEALTH CHECK ====================
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# ==================== PORTS ====================
EXPOSE 80

# ==================== STARTUP ====================
# Set ServerName to avoid Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Start Apache and supervisor
CMD service cron start && supervisord -c /etc/supervisor/supervisord.conf && apache2-foreground