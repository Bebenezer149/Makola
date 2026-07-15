FROM php:8.3-fpm-alpine

# Install packages
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    postgresql-dev

# ─── FIX 1: ALIGN USER GROUPS FOR ALPINE NGINX ───────────────────
# Add the 'nginx' user to the 'www-data' group so they can share files
RUN usermod -aG www-data nginx || addgroup nginx www-data

# Configure GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    bcmath \
    gd

# PHP configuration for file uploads
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 60" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "upload_tmp_dir = /tmp" >> /usr/local/etc/php/conf.d/uploads.ini

# Make tmp writable
RUN chmod 1777 /tmp

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure nginx/supervisor
COPY .docker/nginx.conf /etc/nginx/nginx.conf
COPY .docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create storage directories and set permissions
RUN mkdir -p /var/www/storage/app/public \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/storage/logs \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# ─── FIX 2: FORCE GENERATE THE SYMLINK ───────────────────────────
RUN php artisan storage:link --force

# Copy and set up entrypoint
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
