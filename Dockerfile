FROM php:8.3-fpm-bookworm

ARG UID=1000
ARG GID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer

RUN apt-get update && apt-get install -y \
    ca-certificates \
    git \
    curl \
    gnupg \
    unzip \
    zip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    imagick \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    redis \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g www-data www-data \
    && mkdir -p /var/www/html /tmp/composer \
    && chown -R www-data:www-data /var/www/html /tmp/composer

WORKDIR /var/www/html

COPY Docker/scripts/app-entrypoint.sh /usr/local/bin/app-entrypoint
COPY Docker/scripts/queue-worker.sh /usr/local/bin/queue-worker
COPY Docker/scripts/scheduler.sh /usr/local/bin/scheduler

RUN chmod +x /usr/local/bin/app-entrypoint /usr/local/bin/queue-worker /usr/local/bin/scheduler

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]