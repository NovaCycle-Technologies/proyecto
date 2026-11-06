FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN echo 'date.timezone=America/Montevideo' > /usr/local/etc/php/conf.d/novacycle-timezone.ini

WORKDIR /var/www/html/Proyecto/proyecto
COPY . /var/www/html/Proyecto/proyecto/
