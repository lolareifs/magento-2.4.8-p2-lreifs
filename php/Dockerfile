FROM markoshust/magento-php:8.2-fpm-4

USER root

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev libxml2-dev \
    zip unzip git curl cron nano libicu-dev libxslt-dev libmcrypt-dev libmemcached-dev \
    libmagickwand-dev libwebp-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo pdo_mysql zip intl xsl soap bcmath opcache ftp sockets \
    && rm -rf /var/lib/apt/lists/*

# Opcache disabled in developer mode
RUN echo "opcache.enable=0" > /usr/local/etc/php/conf.d/opcache-disable.ini

# Forze PHP-FPM to listen TCP 0.0.0.0:9000 
RUN sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's|^;listen.allowed_clients.*|;listen.allowed_clients = 127.0.0.1|' /usr/local/etc/php-fpm.d/www.conf

# file write permission to user 'app' 
RUN mkdir -p /usr/local/var/log \
    && chown -R app:app /usr/local/var/log

USER app

# Run PHP-FPM uusing custom www.conf
CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.d/www.conf", "-F"]
