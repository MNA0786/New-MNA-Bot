# ==================== Dockerfile ====================
# Entertainment Tadka Bot v5.0
# Date: 2026-03-05
# Optimized for Render.com deployment

# Use official PHP image with Apache
FROM php:8.2-apache

# ==================== LABELS ====================
LABEL maintainer="Entertainment Tadka"
LABEL version="5.0"
LABEL description="Telegram Bot for Movie Search with Auto Channel Scanner"
LABEL org.opencontainers.image.source="https://github.com/yourusername/entertainment-bot"

# ==================== ENVIRONMENT VARIABLES ====================
ENV ENVIRONMENT=production
ENV BOT_TOKEN=""
ENV BOT_USERNAME=""
ENV BOT_ID=""
ENV ADMIN_IDS=""
ENV API_ID=""
ENV API_HASH=""
ENV PORT=80
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2
ENV APACHE_PID_FILE=/var/run/apache2/apache2.pid

# ==================== SYSTEM DEPENDENCIES ====================
RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    cron \
    supervisor \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# ==================== PHP EXTENSIONS ====================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    pdo \
    zip \
    gd \
    exif \
    pcntl \
    bcmath \
    mbstring \
    xml \
    && docker-php-ext-enable pdo_mysql mysqli zip gd exif pcntl bcmath mbstring xml

# ==================== APACHE CONFIGURATION ====================
# Enable Apache modules
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires \
    && a2enmod deflate \
    && a2enmod proxy \
    && a2enmod proxy_http \
    && a2enmod ssl

# 🔥 CRITICAL: Configure Apache to listen on port 80 and all interfaces
RUN sed -i 's/Listen 80/Listen 0.0.0.0:80/g' /etc/apache2/ports.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "Listen 0.0.0.0:80" > /etc/apache2/ports.conf

# 🔥 Set Apache to run in foreground
RUN echo "PidFile /var/run/apache2/apache2.pid" >> /etc/apache2/apache2.conf

# ==================== APPLICATION SETUP ====================
WORKDIR /var/www/html

# Copy application files
COPY . .

# 🔥 Create necessary directories with proper permissions
RUN mkdir -p cache backups logs /var/log/supervisor \
    && chown -R www-data:www-data cache backups logs /var/log/supervisor \
    && chmod -R 755 cache backups logs /var/log/supervisor \
    && chmod -R 755 /var/www/html \
    && chmod 644 *.php \
    && chmod 644 *.json *.csv 2>/dev/null || true

# 🔥 Create test file for health check
RUN echo "<?php echo 'OK'; ?>" > /var/www/html/health.php
RUN echo "<?php phpinfo(); ?>" > /var/www/html/info.php

# ==================== ENVIRONMENT CONFIGURATION ====================
# Check if .env.example exists and create .env
RUN if [ -f .env.example ]; then \
        cp .env.example .env; \
        chmod 600 .env; \
        echo "✅ .env file created from .env.example"; \
    else \
        echo "⚠️ .env.example not found, creating empty .env"; \
        touch .env; \
        chmod 600 .env; \
    fi

# ==================== CRON SETUP ====================
# Add cron job for backup (runs at 3 AM daily)
RUN echo "0 3 * * * cd /var/www/html && php -r 'require \"New-MNA-Bot.php\";' >> /var/log/cron.log 2>&1" | crontab -

# ==================== SUPERVISOR CONFIGURATION ====================
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ==================== EXPOSE PORTS ====================
EXPOSE 80
EXPOSE 443

# ==================== VOLUMES ====================
VOLUME ["/var/www/html/cache", "/var/www/html/backups", "/var/www/html/logs", "/var/log/supervisor"]

# ==================== HEALTH CHECK ====================
# 🔥 Simple health check that doesn't depend on curl
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD nc -z localhost 80 || exit 1

# ==================== START COMMAND ====================
# 🔥 Start Apache and Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
