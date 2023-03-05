FROM waglpz/vwd-base:8.2

ARG APPUID=0
ARG APPUGID=0
ARG DUSER=www-data

ENV COMPOSER_HOME /var/www/composer

RUN if [ ${APPUID} != 0 -a $(getent passwd ${DUSER}) ]; then \
        userdel -f ${DUSER};                \
        if getent group ${DUSER} ; then     \
            groupdel ${DUSER};              \
        fi;                                 \
        groupadd -g ${APPUGID} ${DUSER};    \
        useradd -r -l -u ${APPUID} -g ${DUSER} ${DUSER}; \
        chown -R ${DUSER}:${DUSER} $COMPOSER_HOME;       \
    fi
