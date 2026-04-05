FROM php:8.2-apache

RUN docker-php-ext-install mysqli
RUN a2enmod rewrite headers

COPY . /var/www/html/
COPY render/start-apache.sh /usr/local/bin/start-apache.sh

RUN mkdir -p /var/www/html/uploads/avatars \
    && chmod +x /usr/local/bin/start-apache.sh \
    && chown -R www-data:www-data /var/www/html/uploads

CMD ["start-apache.sh"]
