#!/bin/bash

# Performs tasks to keep you updated after a pull.

# This inclusion should work in Windows, git bash, Linux ; when calling from the same directory, another directory and PATH...
DIR="${BASH_SOURCE%/*}"
if [[ ! -d "$DIR" ]]; then DIR="$PWD"; fi
. "$DIR/common.inc"

echo -e "${cyan}${highlight}*** composer install ***${resetColor}"
composer install
if [ $? -ne 0 ] ; then
    echo -e "${red}FAILURE{resetColor}"
    exit 1
fi

echo -e "${cyan}${highlight}*** php app/console doctrine:schema:update --force ***${resetColor}"
php app/console doctrine:schema:update --force
if [ $? -ne 0 ] ; then
    echo -e "${red}FAILURE{resetColor}"
    exit 1
fi

"$DIR/assets.sh"
if [ $? -ne 0 ] ; then
    echo -e "${red}FAILURE{resetColor}"
    exit 1
fi

echo "All done."
echo "You may also need to run 'php app/console sogedial:executeDatabaseSetup' in some cases."
