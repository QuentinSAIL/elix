FROM php:8.3-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y \
        git \
        zip \
        unzip \
        wget \
        gnupg2 \
        libzip-dev \
        cron \
        libpq-dev \
        libicu-dev

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN echo "short_open_tag=Off" >> /usr/local/etc/php/php.ini
RUN echo "upload_max_filesize=150M" >> /usr/local/etc/php/php.ini
RUN echo "post_max_size=150M" >> /usr/local/etc/php/php.ini
RUN echo "memory_limit=150M" >> /usr/local/etc/php/php.ini

RUN docker-php-ext-configure intl && docker-php-ext-install intl
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql zip exif bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY --chown=www-data:www-data . /var/www/html

# Change ownership of the storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Change permissions of the storage and cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN a2enmod rewrite && service apache2 restart

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --prefer-dist

ENV PUSHER_APP_ID=
ENV PUSHER_APP_KEY=
ENV PUSHER_APP_SECRET=
ENV PUSHER_APP_CLUSTER=eu
ENV VITE_PUSHER_APP_KEY=
ENV VITE_PUSHER_APP_CLUSTER=eu

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install \
    && npm run build
