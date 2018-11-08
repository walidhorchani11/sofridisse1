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

function check_tool {

    TOOL=$1
    PRESENT=`which "$TOOL" 2>/dev/null`
    if [ -z "$PRESENT" ] ; then
        echo -e "${red}Tool '$TOOL' is not found.${resetColor}"
        exit 1
    fi
}

check_tool cat
check_tool sed
check_tool grep
check_tool mysql
check_tool php
check_tool composer

PARAMFILE=app/config/parameters.yml
PARAMFILE2=app/config/parameters.yml.dist

if [ ! -f $PARAMFILE ] ; then
    if [ ! -f $PARAMFILE2 ] ; then
        echo -e "${red}Parameter file '$PARAMFILE2' not found. One should run this script from the project root.${resetColor}"
        exit 1
    fi

    echo -e "${yellow}Parameter file '$PARAMFILE' not found. Launch composer install (please type 'yes')?${resetColor}"
    read ANSWER
    if [ "$ANSWER" != "yes" ] ; then
        echo "You must answer 'yes' in order to proceed. Good bye."
        exit 0
    fi

    echo -e -n "${cyan}${highlight}Performing composer install... ${resetColor}"

    composer install --prefer-dist
    if [ $? -ne 0 ] ; then
        echo -e "${red}Unexpected failure.${resetColor}"
        exit 1
    fi
    echo -e "${cyan}${highlight}Performing composer install: done.${resetColor}"
fi

echo -n "Retrieving password... "
PASSWORD=`cat $PARAMFILE | grep "database_password" | sed -e 's+^ *database_password *: *\([^ ]*\) *$+\1+g'`
echo "done."

echo -n "Retrieving database user... "
DBUSER=`cat $PARAMFILE | grep "database_user" | sed -e 's+^ *database_user *: *\([^ ]*\) *$+\1+g'`
echo "done."

echo -n "Retrieving database name... "
DBNAME=`cat $PARAMFILE | grep "database_name" | sed -e 's+^ *database_name *: *\([^ ]*\) *$+\1+g'`
echo "done."

echo -n "Retrieving database host... "
DBHOST=`cat $PARAMFILE | grep "database_host" | sed -e 's+^ *database_host *: *\([^ ]*\) *$+\1+g'`
echo "done."

if [ "$PASSWORD" = "null" ]  ; then
    MYSQLCOMMAND="mysql -h $DBHOST -u $DBUSER"
else
    MYSQLCOMMAND="mysql -h $DBHOST -u $DBUSER -p$PASSWORD"
fi

echo "UPDATE client c1,
        (
               SELECT c.code_client, count(*) nb
               FROM promotion pt, produit p , client c, assortiment a
               WHERE p.code_produit=pt.code_produit
               AND (pt.code_client=c.code_client or pt.code_enseigne=c.code_enseigne)
               AND pt.code_entreprise=c.code_entreprise
               AND c.code_assortiment=a.code_assortiment
               AND p.actif=true
               AND pt.date_debut_validite <= NOW()
               AND pt.date_fin_validite >= NOW()
               GROUP BY c.code_client
       ) c2
       SET c1.promotions_compteur=c2.nb
       WHERE c2.code_client = c1.code_client" | $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}SQL issue!!${resetColor}"
fi
echo "done."