#!/bin/sh
set -e

PORT="${PORT:-80}"
sed -i "s/listen 80;/listen ${PORT};/" /etc/nginx/nginx.conf

php artisan config:clear
php artisan cache:clear
php artisan storage:link --force
# Run migrations only once (per deployed release) to avoid repeated schema writes on scale.
# Use a marker file persisted in the container filesystem.
if [ ! -f /tmp/.migrated ]; then
  php artisan migrate --force --no-interaction
  touch /tmp/.migrated
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisor.conf

