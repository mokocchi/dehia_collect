FROM php:7.4-fpm-alpine

RUN apk update && apk add libzip-dev nginx curl su-exec

RUN docker-php-ext-install zip

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    && pecl install apcu \
    && docker-php-ext-enable apcu

RUN apk update && apk add curl-dev openssl-dev

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY default.conf /etc/nginx/conf.d/default.conf

COPY --chown=www-data:www-data ./app /var/www/app

WORKDIR /var/www/app

ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.9.0/wait /tmp/wait
RUN chmod u+x /tmp/wait &&\
    chown 1000:1000 /tmp/wait

RUN curl -L https://github.com/a8m/envsubst/releases/download/v1.1.0/envsubst-`uname -s`-`uname -m` -o /tmp/envsubst && \
    chmod u+x /tmp/envsubst && \
    mv /tmp/envsubst /usr/local/bin

RUN touch .env.local &&\
    chmod u+rw .env.local &&\
    chown www-data:www-data .env.local

RUN mkdir -p /run/nginx

RUN PATH=$PATH:/usr/src/app/vendor/bin:bin

RUN sed -i 's/user nginx;/user www-data;/' /etc/nginx/nginx.conf

CMD ["/bin/sh", "-c", "/tmp/wait &&\
    envsubst < .env.template > .env.local &&\
    su-exec www-data:www-data bin/console doctrine:mongodb:schema:create &&\
    php-fpm -D; nginx -g 'daemon off;'"]