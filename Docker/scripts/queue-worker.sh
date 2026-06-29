#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f artisan ]; then
  echo "artisan not found; queue worker is waiting for Laravel bootstrap."
  exec tail -f /dev/null
fi

exec php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3
