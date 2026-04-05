FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite headers

COPY . /var/www/html/
COPY render/start-apache.sh /usr/local/bin/start-apache.sh

RUN mkdir -p /var/www/html/uploads/avatars \
    && chmod +x /usr/local/bin/start-apache.sh \
    && chown -R www-data:www-data /var/www/html/uploads

CMD ["start-apache.sh"]
