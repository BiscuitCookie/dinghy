FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libsqlite3-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_sqlite mbstring xml zip sockets \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite proxy proxy_http ssl headers

RUN printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/security.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker-queue.sh /usr/local/bin/docker-queue.sh
RUN chmod +x /usr/local/bin/docker-queue.sh

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
