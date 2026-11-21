#!/usr/bin/env sh
set -eu

cd /var/www/html

export PORT="${PORT:-10000}"

# Ensure required writable directories exist with proper ownership
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Render and validate Nginx configuration for Render's dynamic $PORT
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
nginx -t

# Ensure public/storage symlink resolves cleanly
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Rebuild Laravel caches without requiring a database connection
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
