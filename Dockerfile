FROM php:8-cli-alpine

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV TZ Asia/Tokyo
ENV PATH="/project/vendor/bin:${PATH}"
ENV PHP_IDE_CONFIG serverName=host.docker.internal

RUN  apk add --no-cache bash coreutils git libxml2 libstdc++ yaml \
  && apk add --no-cache --virtual=.build-deps autoconf curl-dev libxml2-dev linux-headers gcc g++ make pcre-dev tzdata yaml-dev \
  && docker-php-ext-install -j$(nproc) pdo pdo_mysql opcache pcntl \
  && if [ ! "${HTTP_PROXY}" = "" ]; then pear config-set http_proxy ${HTTP_PROXY}; fi \
  && pecl install -o -f apcu msgpack redis yaml \
  && docker-php-ext-enable apcu msgpack redis yaml \
  && apk del .build-deps \
  && docker-php-source delete \
  && rm -rf /tmp/* /var/tmp/* \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer \
  && composer global require hirak/prestissimo \
  && mkdir -p /project

WORKDIR /project
