#!/bin/bash

# colors
resetColor="\033[0m"
red="\033[31m"
green="\033[32m"
yellow="\033[33m"
blue="\033[34m"
pink="\033[35m"
cyan="\033[36m"
bold="\033[1m"
underline="\033[4m"
highlight="\033[7m"

echo -e "${cyan}${highlight}*** php app/console assets:install ***${resetColor}"
php app/console assets:install
if [ $? -ne 0 ] ; then
    echo -e "${red}FAILURE${resetColor}"
    exit 1
fi

echo -e "${cyan}${highlight}*** php app/console assetic:dump (muted) ***${resetColor}"
php app/console assetic:dump >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}FAILURE${resetColor}"
    exit 1
fi

echo assetic:dump performed successfully
