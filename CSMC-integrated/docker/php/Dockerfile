FROM php:fpm-alpine3.8

RUN apk add --update --no-cache \
    openssh \
    git \
    unzip \
    libxml2-dev \
    nodejs \
    nodejs-npm \
    yarn

RUN set -ex \
    && apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS imagemagick-dev libtool \
    && export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS" \
    && pecl install imagick-3.4.3 \
    && docker-php-ext-enable imagick \
    && apk add --no-cache --virtual .imagick-runtime-deps imagemagick \
    && apk del .phpize-deps

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install bcmath calendar ctype pdo pdo_mysql xml

RUN apk add --no-cache --virtual .persistent-deps \
    libldap

RUN apk update && \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS openldap-dev && \
    docker-php-ext-install ldap && \
    apk del .build-deps && \
    rm -rf /tmp/* /var/cache/apk/*

ADD symfony.ini /usr/local/etc/php/conf.d/
ADD php-fpm.conf /usr/local/etc/
ADD www.conf /usr/local/etc/php-fpm.d/

WORKDIR /code

EXPOSE 9000