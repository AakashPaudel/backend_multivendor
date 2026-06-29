#!/usr/bin/env sh
set -eu

if [ -d /var/www/html/storage ]; then
  mkdir -p /var/www/html/storage/tmp
  mkdir -p /var/www/html/storage/framework/cache
  mkdir -p /var/www/html/storage/framework/sessions
  mkdir -p /var/www/html/storage/framework/views
  mkdir -p /var/www/html/storage/logs
  chown -R www-data:www-data /var/www/html/storage/tmp /var/www/html/storage/framework /var/www/html/storage/logs
  chmod -R ug+rwX /var/www/html/storage/tmp /var/www/html/storage/framework /var/www/html/storage/logs
fi

if [ -d /var/www/html/bootstrap ] || [ ! -e /var/www/html/bootstrap/cache ]; then
  mkdir -p /var/www/html/bootstrap/cache
  chown -R www-data:www-data /var/www/html/bootstrap/cache
  chmod -R ug+rwX /var/www/html/bootstrap/cache
fi

if [ -d /var/www/html/storage/app/public ] && [ ! -e /var/www/html/public/storage ]; then
  php artisan storage:link >/dev/null 2>&1 || true
fi

exec "$@"
