#!/usr/bin/env bash

IMAGES=$(docker images | grep $(basename $PWD) | tee /dev/tty)

read -p "Would you remove images which you see? [Y|n]: " -r RM

if [ "${RM}" = "n" ] ;
then
  echo "You have canceled deleting images."
  exit
fi
echo

echo $(echo  "${IMAGES}" | awk '{print $3}') | xargs docker rmi
