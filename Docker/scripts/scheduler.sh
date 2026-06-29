#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f artisan ]; then
  echo "artisan not found; scheduler cannot start."
  exit 1
fi

while true; do
  php artisan schedule:run --verbose --no-interaction
  sleep "$(expr 60 - "$(date +%S)")"
done