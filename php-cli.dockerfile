FROM php:8.2.1-cli
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
RUN printf '[xdebug]\nxdebug.mode=debug\nxdebug.client_host=host.docker.internal\n' >> /usr/local/etc/php/conf.d/xdebug.ini
WORKDIR /app
