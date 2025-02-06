#!/usr/bin/env bash

GREEN="\e[0;92m"
RESET="\e[0m"

USER_NAME=www-data
IMAGE_NAME=${USER_NAME}/$(basename $PWD)

echo -e "${GREEN}Begin create Docker image '${IMAGE_NAME}' ...${RESET}"

docker build                          \
       --no-cache                     \
       --force-rm                     \
       --tag ${IMAGE_NAME}            \
       --build-arg APPUID=$(id -u)    \
       --build-arg APPUGID=$(id -g)   \
       --build-arg DUSER=${USER_NAME} \
.docker

docker images | grep "$(basename $PWD)"

echo -e "${GREEN}build image ${IMAGE_NAME} done.${RESET}"

echo -e "${GREEN}Run ${IMAGE_NAME} ...${RESET}"

docker run                \
      --user ${USER_NAME} \
      --rm -ti            \
      -v $PWD:/app        \
      -v $PWD/.docker/    \
      -v $PWD/.docker/php/php-ini-overrides.ini:/usr/local/etc/php/conf.d/99-overrides.ini \
${IMAGE_NAME} bash
