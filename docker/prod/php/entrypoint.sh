#!/usr/bin/env sh
set -e

cd /var/www

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Keep writable paths healthy for Laravel in production.
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

if [ ! -f vendor/autoload.php ]; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

exec "$@"
