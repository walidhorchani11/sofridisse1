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

echo -e "${cyan}${highlight} *** Step 1 update primary key *** ${resetColor}"

    echo -n "Performing data conversion on client... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE client SET code_client=CONCAT(code_entreprise, '-',code_client) WHERE code_client NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."



    echo -n "Performing data conversion on assortiment... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE assortiment SET code_assortiment=CONCAT(code_entreprise, '-',code_assortiment) WHERE code_assortiment NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."



    echo -n "Performing data conversion on supplier... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE supplier SET code_supplier=CONCAT(code_entreprise, '-',code_supplier) WHERE code_supplier NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."


echo -e "${cyan}${highlight} *** Step 2 update foreign key*** ${resetColor}"

    echo -n "Performing data conversion on client... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE client SET code_assortiment=CONCAT(code_entreprise, '-',code_assortiment) WHERE code_assortiment NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data conversion on commande... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE commande SET code_client=CONCAT(code_entreprise, '-',code_client) WHERE code_client IS NOT NULL AND code_entreprise IS NOT NULL AND code_client NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data conversion on fos_user... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE fos_user SET code_client=CONCAT(code_entreprise, '-',code_client) WHERE code_client IS NOT NULL AND code_entreprise IS NOT NULL AND code_client NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data conversion on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE promotion SET code_client=CONCAT(code_entreprise, '-',code_client) WHERE code_client IS NOT NULL AND code_entreprise IS NOT NULL AND code_client NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data conversion on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE promotion SET code_supplier=CONCAT(code_entreprise, '-',code_supplier) WHERE code_supplier IS NOT NULL AND code_entreprise IS NOT NULL AND code_supplier NOT LIKE CONCAT(code_entreprise, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -e "${cyan}${highlight} *** Step 3 update primary key tarifications *** ${resetColor}"

    echo -n "Performing data conversion on tarif with tarification... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE tarif SET code_tarif= CONCAT(code_entreprise, '-',SUBSTRING(code_produit, LOCATE('-', code_produit) + 1), '-', code_tarification) WHERE (code_tarif NOT LIKE CONCAT(code_entreprise, '-%') OR code_tarif NOT LIKE CONCAT('%-', code_tarification)) AND code_tarification IS NOT NULL AND code_entreprise IS NOT NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data conversion on tarif with enseigne... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE tarif SET code_tarif= CONCAT(code_entreprise, '-',SUBSTRING(code_produit, LOCATE('-', code_produit) + 1), '-', code_enseigne) WHERE (code_tarif NOT LIKE CONCAT(code_entreprise, '-%') OR code_tarif NOT LIKE CONCAT('%-', code_enseigne)) AND code_enseigne IS NOT NULL AND code_entreprise IS NOT NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -e "${cyan}${highlight} *** Step 4 delete empty tarif *** ${resetColor}"

    echo -n "Performing data deletion on tarif... "
    echo "SET FOREIGN_KEY_CHECKS=0; DELETE FROM tarif WHERE prix_ht IS NULL OR prix_ht <= 0.00;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data deletion on degressif... "
    echo "SET FOREIGN_KEY_CHECKS=0; DELETE FROM degressif WHERE prix_ht IS NULL OR prix_ht <= 0.00;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    
    echo -e "${cyan}${highlight} *** Step 5 drop old association table entreprise_produit if exist *** ${resetColor}"

    echo -n "Performing deletion on old association table entreprise_produit... "
    echo "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS entreprise_produit;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data conversion on product... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE produit SET code_entreprise = SUBSTRING_INDEX(code_produit,'-',1) WHERE code_entreprise NOT LIKE SUBSTRING_INDEX(code_produit,'-',1) OR code_entreprise IS NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo "All done."
