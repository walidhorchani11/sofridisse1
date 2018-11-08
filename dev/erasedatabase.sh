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

PARAMFILE=app/config/parameters.yml
if [ ! -f $PARAMFILE ] ; then
    echo -e "${red}Parameter file '$PARAMFILE' not found. One should run this script from the project root.${resetColor}"
    exit 1
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
    echo -e "${red}Problem with your MySQL connection, please check host, user, password, and database name."
    exit 1
fi
echo "done."

echo
echo -e "Ready to erase database ${green}$DBNAME${resetColor} on host '${green}$DBHOST${resetColor}' (user ${green}$DBUSER${resetColor})."
echo

echo -e "${red}***********************************************************************************************${resetColor}"
echo -e "${red}*** THIS WILL ERASE THE ENTIRE DATABASE, ${yellow}ARE YOU SURE YOU ARE NOT ON THE PRODUCTION SERVER?${red} ***${resetColor}"    
echo -e "${red}***********************************************************************************************${resetColor}"

read ANSWER

if [ "$ANSWER" != "yes" ] ; then
    echo "You must answer 'yes' in order to proceed. Good bye."
    exit 0
fi

echo -n "Dropping database... "
echo "DROP DATABASE \`$DBNAME\`" | $MYSQLCOMMAND
if [ $? -ne 0 ] ; then
    echo -e "${red}MySQL failure.${resetColor}"
    exit 1
fi
echo "done."

echo
echo "You can now launch dev/install.sh"
