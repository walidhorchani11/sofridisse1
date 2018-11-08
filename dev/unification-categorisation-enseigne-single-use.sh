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

echo -e "${cyan}${highlight} *** Step 1 add foreign key properties *** ${resetColor}"

    echo -n "Performing structure conversion on rayon... "
    echo "SET FOREIGN_KEY_CHECKS=0; ALTER TABLE rayon ADD COLUMN code_region VARCHAR(11) NOT NULL; ALTER TABLE rayon ADD CONSTRAINT code_region FOREIGN KEY FK_code_region REFERENCES region (code_region);" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing structure conversion on sous_famille... "
    echo "SET FOREIGN_KEY_CHECKS=0; ALTER TABLE sous_famille ADD COLUMN code_region VARCHAR(11) NOT NULL; ALTER TABLE sous_famille ADD CONSTRAINT code_region FOREIGN KEY FK_code_region REFERENCES region (code_region);" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo -e "${cyan}${highlight} *** Step 2 set code_region value default for rayon and sous_famille *** ${resetColor}"

    echo -n "Performing data setting on rayon... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE rayon SET code_region=2 WHERE code_region IS NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing data setting on sous_famille... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE sous_famille SET code_region=2 WHERE code_region IS NULL;" | $MYSQLCOMMAND $DBNAME >/dev/null
    
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."


echo -e "${cyan}${highlight} *** Step 3 update old foreign key *** ${resetColor}"

    echo -n "Performing update data on client... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE client set code_enseigne= CONCAT('2-', code_enseigne) WHERE code_enseigne NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on enseigne... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE enseigne set code_enseigne= CONCAT('2-', code_enseigne) WHERE code_enseigne NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on famille... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE famille set code_rayon= CONCAT('2-', code_rayon) WHERE code_rayon NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on famille... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE famille set code_famille= CONCAT('2-', code_famille) WHERE code_famille NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on produit... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE produit set code_secteur= CONCAT('2-', code_secteur) WHERE code_secteur NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on produit... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE produit set code_rayon= CONCAT('2-', code_rayon) WHERE code_rayon NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."
    
    echo -n "Performing update data on produit... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE produit set code_famille= CONCAT('2-', code_famille) WHERE code_famille NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on produit... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE produit set code_sous_famille= CONCAT('2-', code_sous_famille) WHERE code_sous_famille NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on promotion... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE promotion set code_enseigne= CONCAT(code_region, '-', code_enseigne) WHERE code_enseigne NOT LIKE CONCAT(code_region, '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on rayon... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE rayon set code_rayon= CONCAT('2-', code_rayon) WHERE code_rayon NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on rayon... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE rayon set code_secteur= CONCAT('2-', code_secteur) WHERE code_secteur NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on secteur... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE secteur set code_secteur= CONCAT('2-', code_secteur) WHERE code_secteur NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on sous_famille... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE sous_famille set code_sous_famille= CONCAT('2-', code_sous_famille) WHERE code_sous_famille NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on sous_famille... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE sous_famille set code_famille= CONCAT('2-', code_famille) WHERE code_famille NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

    echo -n "Performing update data on tarif... "
    echo "SET FOREIGN_KEY_CHECKS=0; UPDATE tarif set code_enseigne= CONCAT('2-', code_enseigne) WHERE code_enseigne NOT LIKE CONCAT('2', '-%');" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo "All done."
