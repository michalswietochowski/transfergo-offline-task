FROM php:8.2.1-cli
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions xdebug xsl
RUN printf '[xdebug]\nxdebug.mode=debug\nxdebug.client_host=host.docker.internal\n' >> /usr/local/etc/php/conf.d/xdebug.ini
WORKDIR /app
