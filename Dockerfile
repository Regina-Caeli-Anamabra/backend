# Set the base image to PHP with required extensions
FROM php:8.1-fpm

# Install dependencies for Laravel
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql bcmath opcache

# Set the working directory inside the container
WORKDIR /var/www

# Copy the Laravel application files into the container
COPY . /var/www

# Install Composer to manage PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Laravel dependencies
RUN composer install --no-interaction

# Set permissions for Laravel's storage and cache
RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose port 9000 to allow Nginx to communicate with the application
EXPOSE 9000

# Command to run the PHP-FPM server
CMD ["php-fpm"]
