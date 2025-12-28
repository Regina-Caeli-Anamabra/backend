FROM php:8.2 as php

RUN apt-get update -y \
 && apt-get install -y unzip libpq-dev libcurl4-gnutls-dev

RUN docker-php-ext-install pdo pdo_mysql bcmath \
 && docker-php-ext-configure pcntl --enable-pcntl \
 && docker-php-ext-install pcntl

# Create non-root user
RUN groupadd -g 1000 appuser \
 && useradd -u 1000 -g appuser -m appuser

WORKDIR /app
COPY . .

# Fix permissions
RUN chown -R appuser:appuser /app
RUN chmod +x ./docker/entrypoint.sh

RUN echo "max_execution_time = 300" >> /usr/local/etc/php/php.ini

COPY --from=composer:2.7.4 /usr/bin/composer /usr/bin/composer

ENV PORT=8000

# Switch to non-root user
USER appuser

ENTRYPOINT ["./docker/entrypoint.sh"]
