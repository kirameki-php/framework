FROM php:8-cli-alpine

ENV PATH="${PATH}:/project/vendor/bin"
ENV TERM xterm-256color
ENV TZ Asia/Tokyo

COPY docker/files /

RUN set -e \
  && apk add --no-cache bash coreutils git icu-libs libxml2 libstdc++ tzdata \
  && apk add --no-cache --virtual=.build-deps autoconf curl-dev icu-dev libxml2-dev linux-headers gcc g++ make pcre-dev \
  && docker-php-ext-install -j$(nproc) intl pdo pdo_mysql opcache pcntl \
  && pecl install -o -f apcu igbinary msgpack redis \
  && docker-php-ext-enable apcu igbinary msgpack redis \
  && apk del .build-deps \
  && docker-php-source delete \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
  && mkdir -p /project

WORKDIR /project
