#!/bin/bash

# colors
resetColor="\x1b[0m"
red="\x1b[31m"
green="\x1b[32m"
yellow="\x1b[33m"
blue="\x1b[34m"
pink="\x1b[35m"
cyan="\x1b[36m"
bold="\x1b[1m"
underline="\x1b[4m"
highlight="\x1b[7m"

PARAMFILE=app/config/parameters.yml
if [ ! -f $PARAMFILE ] ; then
    echo -e "${red}Parameter file '$PARAMFILE' not found. One should run this script from the project root.${resetColor}"
    exit 1
fi

echo -n "Retrieving password... "
PASSWORD=`cat $PARAMFILE | grep "database_password" | sed -e 's+^ *database_password *: *\([^ ]*\) *$+\1+g'`
echo "done."

echo -n "Retrieving database name... "
DBNAME=`cat $PARAMFILE | grep "database_name" | sed -e 's+^ *database_name *: *\([^ ]*\) *$+\1+g'`
echo "done."

echo -n "Retrieving database user... "
DBUSER=`cat $PARAMFILE | grep "database_user" | sed -e 's+^ *database_user *: *\([^ ]*\) *$+\1+g'`
echo "done."

echo -n "Retrieving database host... "
DBHOST=`cat $PARAMFILE | grep "database_host" | sed -e 's+^ *database_host *: *\([^ ]*\) *$+\1+g'`
echo "done."

if [ "$PASSWORD" = "null" ]  ; then
    MYSQLCOMMAND="mysql -h $DBHOST -u $DBUSER"
else
    MYSQLCOMMAND="mysql -h $DBHOST -u $DBUSER -p$PASSWORD"
fi

echo -n "Checking SQL connection and database name... "
echo "SHOW TABLES;" | $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}failed. Invalid MySQL user password or database name, or a connection problem.${resetColor}"
    exit 1
fi
echo "done."

echo -e "${cyan}${highlight} *** Step 1 *** ${resetColor}"
for TABLE in commande ; do
    echo -n "Performing data conversion on $TABLE... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE $TABLE SET validator_id=user_id WHERE validator_id IS NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."
done

echo "All done."
