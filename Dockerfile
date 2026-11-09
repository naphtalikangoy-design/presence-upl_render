# UPL PRESENCE — image Docker pour Render (Laravel 11, PHP 8.2 + Apache, PostgreSQL ou MySQL)
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_pgsql pdo_mysql zip intl gd bcmath opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

# 1) Laravel neuf  2) nos fichiers par-dessus  3) bibliothèques des rapports
WORKDIR /var/www
RUN rm -rf html && composer create-project laravel/laravel html "^11.0" --no-interaction --no-progress
WORKDIR /var/www/html
COPY . /tmp/overlay
RUN cp -a /tmp/overlay/. /var/www/html/ \
 && rm -rf /tmp/overlay database/migrations/0001_01_01_000000_create_users_table.php tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php \
 && composer require barryvdh/laravel-dompdf phpoffice/phpspreadsheet --no-interaction --no-progress \
 && mkdir -p public/uploads/photos \
 && chown -R www-data:www-data storage bootstrap/cache public/uploads

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
EXPOSE 10000
CMD ["start.sh"]
