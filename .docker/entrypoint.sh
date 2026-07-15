#!/bin/sh
set -e

# 1. Update the Nginx port based on Render's dynamic port assignment
PORT="${PORT:-80}"
sed -i "s/listen 80;/listen ${PORT};/" /etc/nginx/nginx.conf

# 2. Clear out any previous local caching to avoid path mismatches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Create the public storage symlink inside Render
php artisan storage:link --force

# 4. Force migrations to ensure your DB tables are up-to-date
php artisan migrate --force --no-interaction

# 5. FIX: Ensure all generated cache and symlink files belong to the web server
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public
chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/public

# 6. Hand off process control over to Supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisor.conf
