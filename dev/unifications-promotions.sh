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

echo -e "${cyan}${highlight} *** Step 1 update primary key properties *** ${resetColor}"

    echo -n "Performing structure conversion on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; ALTER TABLE promotion CHANGE code_promotion code_promotion VARCHAR(55) UNIQUE NOT NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo -e "${cyan}${highlight} *** Step 2 add column and set updated_at *** ${resetColor}"

    echo -n "Performing structure conversion on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; ALTER TABLE promotion ADD updated_at DATETIME;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data conversion on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE promotion SET updated_at = NOW();" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo -e "${cyan}${highlight} *** Step 3 update primary key data *** ${resetColor}"

    echo -n "Performing data conversion on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE promotion SET code_promotion=CONCAT(code_produit, '-', code_type_promo,'-', code_enseigne, code_client, code_category_client, regroupement_client,'-', date_format(date_debut_validite,'%Y%m%d')) WHERE code_promotion NOT LIKE CONCAT(code_produit, '-%') AND code_produit IS NOT NULL AND date_debut_validite IS NOT NULL AND code_type_promo IS NOT NULL AND ((code_enseigne IS NOT NULL AND trim(code_enseigne) !='') OR (code_client IS NOT NULL AND trim(code_client) != '') OR (code_category_client IS NOT NULL AND trim(code_category) != '') OR (regroupement_client IS NOT NULL AND trim(regroupement_client) != ''));" | $MYSQLCOMMAND $DBNAME >/dev/null
    
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo -e "${cyan}${highlight} *** Step 4 clean invalide data *** ${resetColor}"

    echo -n "Performing data conversion on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; DELETE FROM promotion WHERE code_produit IS NULL OR date_debut_validite IS NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo "All done."
