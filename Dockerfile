FROM php:8.3-fpm-alpine

# Install system dependencies & PHP extensions for PostgreSQL
RUN apk add --no-cache nginx supervisor curl libpng-dev libxml2-dev zip unzip git postgresql-dev
RUN docker-php-ext-install pdo_pgsql bcmath gd

# Configure Nginx and Supervisor configurations
COPY .docker/nginx.conf /etc/nginx/nginx.conf
COPY .docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Set working directory
WORKDIR /var/www

# Copy project files
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Run composer installation
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 80

# Clean config cache and run migrations automatically at boot
CMD php artisan config:clear && php artisan migrate --force && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisor.conf