FROM php:8.1-fpm-alpine

ENV PHPUSER=webuser
ENV PHPGROUP=bloembraaden

RUN adduser -g ${PHPGROUP} -s /bin/sh -D ${PHPUSER}

RUN sed -i "s/user www-data/user = ${PHPUSER}/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group www-data/group = ${PHPGROUP}/g" /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/www/bloembraaden/site/htdocs

# https://github.com/docker-library/php/issues/221
RUN set -ex \
    && apk --no-cache add postgresql-libs postgresql-dev \
	&& docker-php-ext-install pgsql pdo_pgsql \
	&& apk del postgresql-dev

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]

