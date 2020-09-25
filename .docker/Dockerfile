FROM olekhy/webapp

ARG APPUID=0
ARG APPUGID=0

ENV COMPOSER_HOME /var/www/composer

RUN if [ ${APPUID} != 0 ];                           \
    then                                             \
        groupadd -g ${APPUGID} waglpz;               \
        useradd -r -l -u ${APPUID} -g waglpz waglpz; \
        chown -R waglpz:waglpz $COMPOSER_HOME;       \
    fi

