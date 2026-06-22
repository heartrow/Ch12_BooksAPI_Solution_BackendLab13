FROM php:8.2-apache

# 1. Install system dependencies required for Composer and extensions
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions needed for your database
RUN docker-php-ext-install pdo_mysql

# 3. Install Composer globally inside the container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set the working directory
WORKDIR /var/www/html

# 5. Copy the application files into the container
COPY . /var/www/html

# 6. Run Composer to generate the missing 'vendor/' folder and autoloader
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

# 7. Point Apache's DocumentRoot to the 'public/' directory for Slim
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# 8. Enable Apache's rewrite module (Fixes the RewriteEngine crash)
RUN a2enmod rewrite

# 9. Set proper file permissions so Apache can write to your rate-limit files
RUN chown -R www-data:www-data /var/www/html