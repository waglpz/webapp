FROM waglpz/vwd-base:8.3-fpm

ARG APPUID=0
ARG APPUGID=0
ARG DUSER=www-data

RUN apt-get update && apt-get install -y curl unzip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer --version

RUN if [ "${APPUID}" != "0" ] && getent passwd "${DUSER}" > /dev/null; then \
        userdel -f "${DUSER}";               \
        if getent group "${DUSER}" > /dev/null; then \
            groupdel "${DUSER}";             \
        fi;                                  \
        groupadd -g "${APPUGID}" "${DUSER}"; \
        useradd -l -u "${APPUID}" -g "${DUSER}" "${DUSER}"; \
    fi
