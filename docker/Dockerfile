FROM php:8-cli-alpine

ENV PATH="${PATH}:/project/vendor/bin"
ENV TERM xterm-256color
ENV TZ Asia/Tokyo

RUN  apk add --no-cache bash coreutils git gmp libxml2 libstdc++ \
  && apk add --no-cache --virtual=.build-deps autoconf curl-dev gmp-dev libxml2-dev linux-headers gcc g++ make pcre-dev tzdata \
  && docker-php-ext-install -j$(nproc) bcmath gmp pdo pdo_mysql opcache pcntl \
  && if [ ! "${HTTP_PROXY}" = "" ]; then pear config-set http_proxy ${HTTP_PROXY}; fi \
  && pecl install -o -f apcu igbinary msgpack redis \
  && docker-php-ext-enable apcu igbinary msgpack redis \
  && apk del .build-deps \
  && docker-php-source delete \
  && rm -rf /tmp/* /var/tmp/* \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
  && mkdir -p /project

WORKDIR /project
