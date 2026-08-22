ARG PHP_IMAGE=php:8.3.24-fpm-alpine3.22
ARG NODE_IMAGE=node:22.23.1-alpine3.24
ARG COMPOSER_IMAGE=composer:2.10.2

FROM ${COMPOSER_IMAGE} AS composer-bin

FROM ${PHP_IMAGE} AS builder
WORKDIR /var/www
# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    icu-dev \
    postgresql-dev \
    mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    zip \
    gd \
    intl \
    opcache \
    && rm -rf /var/cache/apk/*
# Composer được pin để build không thay đổi ngoài ý muốn.
COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
# Copy application code for dependency installation
COPY . /var/www
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache
# Install Composer dependencies
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --classmap-authoritative \
    --no-dev
# Generate application key and optimize (if needed, typically done in entrypoint)
# RUN php artisan key:generate
# RUN php artisan config:cache
# RUN php artisan route:cache
# RUN php artisan view:cache

FROM ${NODE_IMAGE} AS node-build
WORKDIR /app
COPY package*.json vite.config.* ./
RUN npm ci
# Tailwind v4 (@source trong app.css) quét class từ resources + modules.
# Nếu không COPY modules → CSS production thiếu utility → UI gãy so với local.
COPY resources ./resources
COPY modules ./modules
# Pagination views (nếu có trong vendor lúc build local; optional stub path)
# Không copy full vendor — chỉ cần path tồn tại nếu @source trỏ tới đó.
RUN mkdir -p vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    storage/framework/views
RUN npm run build

FROM ${PHP_IMAGE}
WORKDIR /var/www

# Install runtime dependencies, PLUS nginx + supervisor to replace `php artisan serve`
RUN apk add --no-cache \
    libzip \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    font-dejavu \
    font-liberation \
    icu \
    mysql-client \
    mariadb-connector-c \
    $PHPIZE_DEPS \
    mariadb-connector-c-dev \
    libzip-dev \
    libreoffice \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql zip gd intl opcache \
    && php -r 'foreach (["dom", "fileinfo", "gd", "mbstring", "SimpleXML", "xml", "xmlreader", "xmlwriter", "zip"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Missing PHP extension: {$extension}\n"); exit(1); } }' \
    && rm -rf /var/cache/apk/*

# App code, entrypoint, and config files
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php-overrides.ini /usr/local/etc/php/conf.d/zz-app-runtime.ini
RUN chmod +x /entrypoint.sh \
    && mkdir -p \
        /var/lib/nginx/tmp/client_body \
        /var/lib/nginx/tmp/proxy \
        /var/lib/nginx/tmp/fastcgi \
        /var/lib/nginx/tmp/uwsgi \
        /var/lib/nginx/tmp/scgi \
    && chown -R www-data:www-data /var/lib/nginx

# Code app trước, rồi ghi đè assets Vite đã build (tránh builder ghi đè mất build)
COPY --from=builder /var/www /var/www
COPY --from=node-build /app/public/build /var/www/public/build

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
# nginx + php-fpm run together under supervisord, instead of the single-threaded
# `php artisan serve` dev server, which is not safe for production traffic.
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
