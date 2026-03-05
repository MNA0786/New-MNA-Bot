# ==================== Dockerfile ====================
# Entertainment Tadka Bot v5.0
# Date: 2026-03-05

# Use official PHP image with Apache
FROM php:8.2-apache

# ==================== LABELS ====================
LABEL maintainer="Entertainment Tadka"
LABEL version="5.0"
LABEL description="Telegram Bot for Movie Search with Auto Channel Scanner"

# ==================== ENVIRONMENT VARIABLES ====================
ENV ENVIRONMENT=production
ENV BOT_TOKEN=""
ENV BOT_USERNAME=""
ENV BOT_ID=""
ENV ADMIN_IDS=""
ENV API_ID=""
ENV API_HASH=""

# ==================== SYSTEM DEPENDENCIES ====================
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    cron \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# ==================== PHP EXTENSIONS ====================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    zip \
    gd \
    exif \
    pcntl

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate ratelimit

# ==================== APPLICATION SETUP ====================
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create necessary directories
RUN mkdir -p cache backups logs \
    && chown -R www-data:www-data cache backups logs \
    && chmod -R 755 cache backups logs \
    && chmod 644 *.php \
    && chmod 644 *.json *.csv 2>/dev/null || true

# ==================== ENVIRONMENT CONFIGURATION ====================
# Copy .env.example to .env (will be overridden by docker-compose or kubernetes secrets)
COPY .env.example .env
RUN chmod 600 .env

# ==================== CRON SETUP ====================
# Add cron job for backup
RUN echo "0 3 * * * cd /var/www/html && php -r 'require \"New-MNA-Bot.php\";' > /dev/null 2>&1" | crontab -

# ==================== SUPERVISOR CONFIGURATION ====================
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ==================== HEALTH CHECK ====================
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/New-MNA-Bot.php?test || exit 1

# ==================== PORTS ====================
EXPOSE 80
EXPOSE 443

# ==================== VOLUMES ====================
VOLUME ["/var/www/html/cache", "/var/www/html/backups", "/var/www/html/logs"]

# ==================== START COMMAND ====================
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]