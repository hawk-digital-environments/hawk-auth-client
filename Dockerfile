# ===============================
# php environment
# ===============================
# syntax = docker/dockerfile:1.2
FROM neunerlei/php:8.4-fpm-alpine AS app_dev
ARG APP_ENV=prod
ENV APP_ENV=${APP_ENV}
# @see https://aschmelyun.com/blog/fixing-permissions-issues-with-docker-compose-and-php/
ARG DOCKER_RUNTIME=docker
ARG DOCKER_GID=1000
ARG DOCKER_UID=1000

ENV DOCKER_RUNTIME=${DOCKER_RUNTIME:-docker}
ENV APP_ENV=dev

# Add sudo command
RUN --mount=type=cache,id=apk-cache,target=/var/cache/apk rm -rf /etc/apk/cache && ln -s /var/cache/apk /etc/apk/cache && \
    apk update && apk upgrade && apk add \
	  sudo

# Add Composer
COPY --from=index.docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer

# Install xdebug
RUN --mount=type=cache,id=apk-cache,target=/var/cache/apk rm -rf /etc/apk/cache && ln -s /var/cache/apk /etc/apk/cache && \
	apk update && apk upgrade && apk add --no-cache $PHPIZE_DEPS \
      && apk add --update linux-headers \
      && pecl install xdebug-3.4.1 \
      && docker-php-ext-enable xdebug

# Because we inherit from the prod image, we don't actually want the prod settings
COPY docker/php/config/php.dev.ini /usr/local/etc/php/conf.d/zzz.app.dev.ini
RUN rm -rf /usr/local/etc/php/conf.d/zzz.app.prod.ini

# Recreate the www-data user and group with the current users id
RUN --mount=type=cache,id=apk-cache,target=/var/cache/apk rm -rf /etc/apk/cache && ln -s /var/cache/apk /etc/apk/cache && \
	apk update && apk upgrade && apk add shadow \
       && (userdel -r www-data || true) \
       && (groupdel -f www-data || true) \
       && groupadd -g ${DOCKER_GID} www-data \
       && adduser -u ${DOCKER_UID} -D -S -G www-data www-data

USER www-data

RUN composer global config --no-plugins allow-plugins.neunerlei/dbg-global true \
    && composer global require neunerlei/dbg-global

# ===============================
# app root
# ===============================
FROM node:23-bookworm AS app_node
ARG DOCKER_UID=1000
ARG DOCKER_GID=1000
ENV UID=${DOCKER_UID}
ENV GID=${DOCKER_GID}

RUN set -eux; \
    if getent passwd "${UID}"; then userdel -r "$(getent passwd "${UID}" | cut -d: -f1)"; fi; \
    if getent group "${GID}"; then groupdel -f "$(getent group "${GID}" | cut -d: -f1)"; fi; \
    groupadd --gid "$GID" builder || true; \
    adduser --uid "$UID" --gid "$GID" --disabled-password --gecos "" builder

USER ${UID}
