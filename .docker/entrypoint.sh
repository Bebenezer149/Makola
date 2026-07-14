#!/bin/sh
set -e

PORT="${PORT:-80}"
sed -i "s/listen 80;/listen ${PORT};/" /etc/nginx/nginx.conf

php artisan config:clear
php artisan cache:clear
php artisan storage:link --force
php artisan migrate --force --no-interaction


exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisor.conf

