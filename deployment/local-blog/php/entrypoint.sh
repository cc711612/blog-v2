#!/bin/sh
set -eu

cd /var/www/html

if [ -f composer.json ] && [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist
fi

exec php-fpm
