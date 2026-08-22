#!/bin/sh
set -e

# Ensure storage & cache directories exist
mkdir -p /var/www/storage/framework/{cache,sessions,views}
mkdir -p /var/www/bootstrap/cache

# Nginx worker chạy bằng www-data. Alpine tạo các thư mục tạm này cho user
# nginx, nên request có body lớn sẽ bị 500 trước khi đến được PHP-FPM.
mkdir -p \
    /var/lib/nginx/tmp/client_body \
    /var/lib/nginx/tmp/proxy \
    /var/lib/nginx/tmp/fastcgi \
    /var/lib/nginx/tmp/uwsgi \
    /var/lib/nginx/tmp/scgi
chown -R www-data:www-data /var/lib/nginx

# Generate app key if not set
if ! grep -q "APP_KEY=" /var/www/.env || [ -z "$(grep APP_KEY= /var/www/.env | cut -d '=' -f2)" ]; then
    echo ">> Generating APP_KEY..."
    php artisan key:generate --force
fi

# Mỗi image chứa source/Blade mới; luôn dọn cache cũ kể cả khi production
# không bật RUN_MIGRATIONS.
echo ">> Clearing stale application caches..."
php artisan optimize:clear || true

# Handle migrations
if [ "${APP_ENV}" != "production" ] || [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo ">> Running migrations..."
    php artisan migrate --force

    # Đồng bộ permission/role sau migrate — tránh menu/chức năng mất sau deploy
    echo ">> Syncing permissions & roles..."
    php artisan permissions:sync || echo "!! permissions:sync failed (non-fatal)"

else
    echo ">> Skipping migrations (APP_ENV=production and RUN_MIGRATIONS not set)"
fi

# Handle seeders
if [ "${APP_ENV}" != "production" ] || [ "${RUN_SEEDERS}" = "true" ]; then
    echo ">> Running seeders..."
    php artisan db:seed --force
else
    echo ">> Skipping seeders (APP_ENV=production and RUN_SEEDERS not set)"
fi

exec "$@"
