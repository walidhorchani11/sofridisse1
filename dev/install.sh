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

echo -e "${red}************************************************************${resetColor}"
echo -e "${red}*** DO NOT USE THIS SCRIPT ON THE PRODUCTION SERVER      ***${resetColor}"
echo -e "${red}************************************************************${resetColor}"
echo
echo -e "Use it to set up development environment after (a) git clone or (b) dev/erasedatabase.sh."
echo -e "It will perform the following operations:"
echo -e " - install dependencies (if needed, e.g. after git clone)     (composer install)"
echo -e " - create the database (if needed)"
echo -e " - update/create database schema (if needed)                  (doctrine:schema:update --force)"
echo -e " - allow you to create and configure the admin user"
echo -e " - install assets                                             (assets:install, assetic:dump)"
echo -e " - set up data for development (such as zones)                (sogedial:executeDatabaseDevSetup)"
echo -e " - import database                                            (sogedial:executeDatabaseSetup)"
echo
echo -e "${yellow}*** BEFORE RUNNING THIS SCRIPT, REMEMBER TO:             ***${resetColor}"
echo -e "${yellow}*** Configure app/config/parameters.yml:masterEnterprise ***${resetColor}"
echo -e "${yellow}*** Put appropriate CSV files into web/uploads/import    ***${resetColor}"
echo

if [ -f $PARAMFILE ] ; then
    MASTERENTERPRISE=`cat $PARAMFILE | grep "masterEnterprise" | sed -e 's+^ *masterEnterprise *: *\([^ ]*\) *$+\1+g'`
    echo -e "Master enterprise set to: ${green}$MASTERENTERPRISE${resetColor}"
    echo
fi

echo -e "Do you wish to proceed? (please answer 'yes')"

read ANSWER

if [ "$ANSWER" != "yes" ] ; then
    echo "You must answer 'yes' in order to proceed. Good bye."
    exit 0
fi

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

echo -e -n "${cyan}${highlight}Creating database if needed... ${resetColor}"
echo "CREATE DATABASE IF NOT EXISTS \`$DBNAME\` CHARACTER SET utf8 COLLATE utf8_general_ci;" | $MYSQLCOMMAND
if [ $? -ne 0 ] ; then
    echo -e "${red}MySQL failure.${resetColor}"
    exit 1
fi
echo -e "${cyan}${highlight}done.${resetColor}"

echo -e "${cyan}${highlight}Updating schema...${resetColor}"
php app/console doctrine:schema:update --force
if [ $? -ne 0 ] ; then
    echo -e "${red}Unexpected failure.${resetColor}"
    exit 1
fi
echo -e "${cyan}${highlight}Updating schema: done.${resetColor}"

echo -e "${green}Please create a superuser (e.g., admin / adminmail@example.com / adminpass).${resetColor}"
php app/console fos:user:create
if [ $? -ne 0 ] ; then
    echo -e "${red}Unexpected failure.${resetColor}"
    exit 1
fi

echo -e "${green}Please type the admin user name you have just chosen (e.g., \"admin\") and then \"ROLE_ADMIN\".${resetColor}"
php app/console fos:user:promote
if [ $? -ne 0 ] ; then
    echo -e "${red}Unexpected failure.${resetColor}"
    exit 1
fi

echo -e -n "${cyan}${highlight}Setting the name of the salesperson... ${resetColor}"
echo "UPDATE fos_user SET nom_utilisateur=\"D'Artigny Le Breton\",prenom_utilisateur=\"Jean-Louis Philippe\" WHERE roles LIKE '%ROLE_ADMIN%';" | $MYSQLCOMMAND $DBNAME
if [ $? -ne 0 ] ; then
    echo -e "${red}MySQL failure.${resetColor}"
    exit 1
fi
echo -e "${cyan}${highlight}done.${resetColor}"

echo -e "${cyan}${highlight}Performing assets:install...${resetColor}"
php app/console assets:install
if [ $? -ne 0 ] ; then
    echo -e "${red}Unexpected failure.${resetColor}"
    exit 1
fi
echo -e "${cyan}${highlight}Performing assets:install: done.${resetColor}"

echo -e "${cyan}${highlight}Performing assetic:dump...${resetColor}"
php app/console assetic:dump
if [ $? -ne 0 ] ; then
    echo -e "${red}Unexpected failure.${resetColor}"
    exit 1
fi
echo -e "${cyan}${highlight}Performing assetic:dump: done.${resetColor}"

echo -e -n "${cyan}${highlight}Setting up data for development... ${resetColor}"
php app/console sogedial:executeDatabaseDevSetup
if [ $? -ne 0 ] ; then
    echo -e "${red}Unexpected failure.${resetColor}"
    exit 1
fi
echo -e "${cyan}${highlight}done.${resetColor}"

echo -e "${cyan}${highlight}Setting up database...${resetColor}"
php app/console sogedial:executeDatabaseSetup
if [ $? -ne 0 ] ; then
    echo -e "${red}Unexpected failure.${resetColor}"
    exit 1
fi
echo -e "${cyan}${highlight}Setting up database: done.${resetColor}"

echo
echo "All done."
echo
echo "Now you should log in as admin using credentials you have just created, and activate at least one user."
