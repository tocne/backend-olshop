# Base PHP 8.2 with CLI (bukan FPM)
FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# Install PostgreSQL extension
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose application port
EXPOSE 8000

# Start Laravel (cache dibuat ketika container startup)
CMD php artisan config:clear \
    && php artisan migrate --force || true \
    && php artisan serve --host 0.0.0.0 --port 8000
