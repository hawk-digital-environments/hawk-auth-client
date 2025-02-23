# syntax = edrevo/dockerfile-plus
# @see https://aschmelyun.com/blog/fixing-permissions-issues-with-docker-compose-and-php/
# Extension of the examples/Dockerfile file to run as part of the dev environment

INCLUDE+ ./examples/Dockerfile

ARG DOCKER_RUNTIME=docker
ARG DOCKER_GID=1000
ARG DOCKER_UID=1000

USER root

RUN (id -u www-data &>/dev/null && userdel -r www-data || true) && \
    (getent group www-data &>/dev/null && groupdel -f www-data || true) && \
    groupadd -g ${DOCKER_GID} www-data && \
    useradd -u ${DOCKER_UID} -d /var/www -s /usr/sbin/nologin -g www-data www-data
RUN chown -R www-data:www-data /var/www

USER www-data

RUN composer global config --no-plugins allow-plugins.neunerlei/dbg-global true \
    && composer global require neunerlei/dbg-global
