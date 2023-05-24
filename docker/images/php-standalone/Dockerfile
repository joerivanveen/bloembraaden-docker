FROM php:8.1-fpm-alpine

ARG ROOT_DIR=/usr/share/nginx

WORKDIR ${ROOT_DIR}

RUN apk upgrade && \
    # Add dependencies
    apk add --no-cache \
      apk-cron \
      # ext-gd
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
      libwebp-dev \
      # ext-intl
      icu-dev \
      libzip-dev \
      # postgres
      libpq-dev \
      postgresql-client \
      # Includes envsubst
      gettext \
      # needed for wait-for-it
      bash \
      # for mkcert
      wget \
    && \
    docker-php-ext-configure gd \
      --with-jpeg \
      --with-webp \
      --with-freetype  \
    && \
    # Install php extensions
    docker-php-ext-install \
      pdo_pgsql \
      zip \
      gd \
      intl \
      exif \
    && \
    docker-php-ext-enable \
      exif \
    && \
    # Get mkcert
    wget -c https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-arm64 -O mkcert \
      && chmod u+x mkcert \
      && mv mkcert /usr/bin/mkcert

COPY ./config/bloembraaden/config.json    /usr/share/nginx/config.json
COPY ./scripts                            /tmp/scripts

RUN chmod +x /tmp/scripts/*
RUN chmod 644 config.json

COPY ./config/cron/crontab /etc/crontabs/www-user/crontab
RUN chmod 644 /etc/crontabs/www-user/crontab && \
    crontab -u www-data /etc/crontabs/www-user/crontab

ENV ROOT_DIR=${ROOT_DIR}

CMD ["/tmp/scripts/entrypoint.sh"]
