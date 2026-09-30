```sh
#!/bin/sh

set -e

# ============================================================
# 1. Configure Nginx for Render's dynamic port
# ============================================================

PORT="${PORT:-80}"

sed -i "s/listen 80;/listen ${PORT};/" /etc/nginx/nginx.conf


# ============================================================
# 2. Run database migrations FIRST
#
# IMPORTANT:
# The application uses the database cache driver.
# Therefore, the cache table must exist before running
# commands such as `php artisan cache:clear`.
# ============================================================

echo "Running database migrations..."

php artisan migrate --force --no-interaction


# ============================================================
# 3. Clear Laravel configuration and route caches
#
# We intentionally DO NOT run `cache:clear` here.
# The database-backed cache requires the `cache` table,
# and there is no need to clear the production cache on
# every container startup.
# ============================================================

echo "Clearing Laravel configuration cache..."

php artisan config:clear

echo "Clearing Laravel route cache..."

php artisan route:clear


# ============================================================
# 4. Create the public storage symlink
# ============================================================

echo "Creating storage symlink..."

php artisan storage:link --force


# ============================================================
# 5. Fix permissions
#
# Ensure Laravel can write to storage and bootstrap/cache.
# ============================================================

echo "Updating Laravel permissions..."

chown -R www-data:www-data \
    /var/www/storage \
    /var/www/bootstrap/cache \
    /var/www/public

chmod -R 775 \
    /var/www/storage \
    /var/www/bootstrap/cache \
    /var/www/public


# ============================================================
# 6. Start Supervisor
#
# Supervisor will manage Nginx and PHP-FPM according to
# supervisor.conf.
# ============================================================

echo "Starting Supervisor..."

exec /usr/bin/supervisord \
    -c /etc/supervisor/conf.d/supervisor.conf
```
