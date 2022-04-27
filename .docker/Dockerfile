FROM olekhy/webapp

ARG APPUID=0
ARG APPUGID=0
ARG DUSER=waglpz

ENV COMPOSER_HOME /var/www/composer

RUN if [ ${APPUID} != 0 ];                               \
    then                                                 \
        groupadd -g ${APPUGID} ${DUSER};                 \
        useradd -r -l -u ${APPUID} -g ${DUSER} ${DUSER}; \
        chown -R ${DUSER}:${DUSER} $COMPOSER_HOME;       \
    fi
