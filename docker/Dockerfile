FROM php:cli-alpine

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV PATH="/project/vendor/bin:${PATH}"
ENV PHP_IDE_CONFIG serverName=host.docker.internal
ENV TERM xterm-256color
ENV TZ Asia/Tokyo

RUN  apk add --no-cache bash coreutils git libxml2 libstdc++ yaml \
  && apk add --no-cache --virtual=.build-deps autoconf curl-dev gmp-dev libxml2-dev linux-headers gcc g++ make pcre-dev tzdata yaml-dev \
  && docker-php-ext-install -j$(nproc) bcmath gmp pdo pdo_mysql opcache pcntl \
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
