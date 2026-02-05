# syntax = docker/dockerfile:1.2
# ===============================
# PHP
# ===============================
FROM neunerlei/php-nginx:8.5 AS php

# Add Composer
COPY --from=index.docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer

# Install xdebug
RUN --mount=type=cache,target=/var/lib/apt/lists \
    --mount=type=cache,target=/var/cache/apt/archives \
    apt-get update && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install xdebug-3.5.0 \
    && docker-php-ext-enable xdebug

USER www-data

RUN composer global config --no-plugins allow-plugins.neunerlei/dbg-global true \
    && composer global require neunerlei/dbg-global

USER root
