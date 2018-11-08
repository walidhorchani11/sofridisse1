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
    MYSQLCOMMAND="mysql -h $DBHOST -u $DBUSER --default_character_set utf8"
else
    MYSQLCOMMAND="mysql -h $DBHOST -u $DBUSER -p$PASSWORD --default_character_set utf8"
fi

echo -n "Checking SQL connection and database name... "
echo "SHOW TABLES;" | $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}failed. Invalid MySQL user password or database name, or a connection problem.${resetColor}"
    exit 1
fi
echo "done."

echo -e "${cyan}${highlight} *** Step 1 truncate and reset etatcommande *** ${resetColor}"

    echo -n "Performing reset data on etatcommande... "
    echo "SET FOREIGN_KEY_CHECKS=0; ALTER TABLE  etatcommande AUTO_INCREMENT = 1; TRUNCATE TABLE etatcommande;" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo -e "${cyan}${highlight} *** Step 2 peupling etatcommande *** ${resetColor}"

    echo -n "Performing data peupling on etatcommande... "
    echo "SET FOREIGN_KEY_CHECKS=0;
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_CURRENT', 'En cours');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_PENDING', 'En attente');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_APPROVED', 'Validée');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_PROCESSED', 'Traitée');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_DELETED', 'Panier supprimé');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_BASKET_VALIDATED', 'Panier validé');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_PENDING_AS400', 'En attente');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_PENDING_PREPARE', 'En cours de préparation');
        INSERT INTO etatcommande (cle, libelle)
        VALUES ('STATUS_FACTURED', 'Facturé');
        INSERT INTO etatcommande (cle, libelle)        
        VALUES ('STATUS_REJECTED', 'Rejeté');" | $MYSQLCOMMAND $DBNAME >/dev/null
    
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
    fi
    echo "done."

echo "All done."
