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
AS400ASSORTNAME = 'Catalogiue'

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

echo -n "Checking SQL connection... "
echo "SHOW VARIABLES LIKE 'collation%';" | $MYSQLCOMMAND >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}FAIL. Invalid MySQL user password or database name, or a connection problem.${resetColor}"
    exit 1
fi
echo "done."

echo -n "Truncate assortiment_client... "
echo "TRUNCATE TABLE assortiment_client"| $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}SQL issue!!${resetColor}"
fi
echo "done."

echo -n "Generate assortiment_client from table Client and Assortiment"
echo "INSERT INTO assortiment_client( code_client, valeur, code_assortiment, as400_assortiment)
SELECT c.code_client, a.valeur, c.code_assortiment, 1
FROM client AS c
INNER JOIN assortiment AS a ON c.code_assortiment = a.code_assortiment"| $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}SQL issue!!${resetColor}"
fi
echo "done."

echo -n "set assortiment_courant"
echo "UPDATE assortiment_client AS t1 INNER JOIN (
        SELECT *
        FROM assortiment_client
        GROUP BY code_client
        )t2 ON t1.id = t2.id
SET t1.assortiment_courant =1"| $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}SQL issue!!${resetColor}"
fi
echo "done."

echo -n "set assortiment_nom"
echo "UPDATE assortiment_client SET assortiment_client.assortiment_nom = 'Catalogue' "| $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}SQL issue!!${resetColor}"
fi
echo "done."
