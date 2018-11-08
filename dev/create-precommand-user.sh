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

echo -n "Checking SQL connection... "
echo "SHOW VARIABLES LIKE 'collation%';" | $MYSQLCOMMAND >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}FAIL. Invalid MySQL user password or database name, or a connection problem.${resetColor}"
    exit 1
fi
echo "done."

#php app/console sogedial:executeDatabaseDevAdminSetup

echo -n "Create sub-entreprise AVION/BATEAU... "
echo "INSERT INTO entreprise
(code_entreprise, code_region, valeur, raison_sociale, adresse1, adresse2, code_postal, ville, pays, actif, etablissement, nom_environnement, entreprise_parent, type_precommande, created_at) VALUES 
(1101, 1, 10, 'LOGIGUA AVION','RUE J.CUGNOT ET G.EIFFEL', 'ZI JARRY', '97122','BAIE-MAHAULT', 'france', 1, 'LOG', 'logigua', 110, 1, NOW()),
(1102, 1, 10, 'LOGIGUA BATEAU','RUE J.CUGNOT ET G.EIFFEL', 'ZI JARRY', '97122','BAIE-MAHAULT', 'france', 1, 'LOG', 'logigua', 110, 2, NOW()),
(1201, 1, 10, 'CADI SURGELES AVION','ZAC HOUELBOURG III', 'VOIE VERTE', '97122','BAIE-MAHAULT', 'france', 1, 'CAD', 'cadi', 120, 1, NOW()),
(1202, 1, 10, 'CADI SURGELES BATEAU','ZAC HOUELBOURG III', 'VOIE VERTE', '97122','BAIE-MAHAULT', 'france', 1, 'CAD', 'cadi', 120, 2, NOW()),
(1301, 1, 10, 'S.E.B. AVION', 'ZONE HOUELBOURG III', 'VOIE VERTE', '97122','BAIE-MAHAULT', 'france', 1, 'SEB', 'sofriber', 130, 1, NOW()),
(1302, 1, 10, 'S.E.B. BATEAU', 'ZONE HOUELBOURG III', 'VOIE VERTE', '97122','BAIE-MAHAULT', 'france', 1, 'SEB', 'sofriber', 130, 2, NOW()),
(2221, 2, 22, 'SOFRIDIS AVION','ZI PLACE D\'ARMES', '', '97122','LAMENTIN', 'france', 1, 'SOF', 'sofridis', 222, 1, NOW()),
(2222, 2, 22, 'SOFRIDIS BATEAU','ZI PLACE D\'ARMES', '', '97122','LAMENTIN', 'france', 1, 'SOF', 'sofridis', 222, 2, NOW()),
(2401, 2, 40, 'M.V.I. AVION','', '', '','', '', 1, 'MVI', 'mvi', 240, 1, NOW()),
(2402, 2, 40, 'M.V.I. BATEAU','', '', '','', '', 1, 'MVI', 'mvi', 240, 2, NOW()),
(3011, 3, 01, 'Societe Frigorifique Guyanaise SAS AVION','4,rue Yves PREVOT', 'Baduel', '97332', 'Cayenne-cedex', 'Guyane_Française', 1, 'SFG', 'sofrigu',  301, 1, NOW()),
(3012, 3, 01, 'Societe Frigorifique Guyanaise SAS BATEAU','4,rue Yves PREVOT', 'Baduel', '97332', 'Cayenne-cedex', 'Guyane_Française', 1, 'SFG', 'sofrigu',  301, 2, NOW()),
(4011, 4, 01, 'Sogedial Exploitation', '419,rue des chantiers BP 5073', '', '76071','Le Havre', 'france', 1, 'SGE', 'sogedial', 401, 1, NOW()),
(4012, 4, 01, 'Sogedial Exploitation', '419,rue des chantiers BP 5073', '', '76071','Le Havre', 'france', 1, 'SGE', 'sogedial', 401, 2, NOW())
ON DUPLICATE KEY UPDATE created_at=NOW()" | $MYSQLCOMMAND $DBNAME >/dev/null
if [ $? -ne 0 ] ; then
    echo -e "${red}SQL issue!!${resetColor}"
fi
echo "done."

for id in `seq 1 1` 
    do 
    client=""
    b=0
    while ((b!=1))
    do
        echo -e "Choose a client code:" 
        read code_client
        client=$code_client
        client_found=$(echo "SELECT COUNT(code_client) FROM client WHERE code_client = '${code_client}'" | $MYSQLCOMMAND $DBNAME | sed -n '2p')
        if [ "$client_found" -eq "1" ] ; then
            echo -e "${green}Client found${resetColor}"
            b=1
        else
            echo -e "${red}Client not found${resetColor}"
        fi
    done

    echo -e "Please choose a username:"
    read fos_username
    echo -e "Please choose a email:"
    read fos_email
    b=0
    #fos_typePreCommande=""
    #while ((b!=1))
    #do
    #    echo -e "Please choose PreCommand type:"
    #    echo -e "[1] AVION"
    #    echo -e "[2] BATEAU"
    #    read fos_typePreCommande
    #    if [ "$fos_typePreCommande" -eq "1" ] || [ "$fos_typePreCommande" -eq "2" ] ; then
    #        b=1
    #    fi
    #done
    fos_typePreCommande="2"
    entrepriseCode=(${code_client//-/ })
    fos_entrepriseCourante="$entrepriseCode$fos_typePreCommande"
    #if [ $fos_typePreCommande -ne "1" ] && [ $fos_typePreCommande -ne "2" ] ; then
    #    echo -e "${red}Bad choice.${resetColor}"
    #    exit 1
    #fi
    echo -e "Please choose a password:"
    read -s fos_password

    php app/console fos:user:create $fos_username $fos_email $fos_password
    if [ $? -ne 0 ] ; then
        echo -e "${red}Unexpected failure.${resetColor}"
        exit 1
    fi
    php app/console fos:user:promote $fos_username ROLE_USER
    
    meta_command="Eprecommand_${fos_username}_${client}_${RANDOM}"

    echo -ne "Create meta-client..."
    echo "INSERT INTO metaClient (code_meta, libelle, created_at) VALUES ('$meta_command', '$meta_command', NOW())" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
        exit 1
    fi

    echo -ne "update fos_user $fos_username..."

    echo "UPDATE fos_user fu
    INNER JOIN zone z1 ON z1.code_entreprise = $fos_entrepriseCourante AND z1.temperature LIKE 'SEC'
    INNER JOIN zone z2 ON z2.code_entreprise = $fos_entrepriseCourante AND z2.temperature LIKE 'FRAIS'
    INNER JOIN zone z3 ON z3.code_entreprise = $fos_entrepriseCourante AND z3.temperature LIKE 'SURGELE'
    SET fu.meta = '$meta_command', fu.entreprise_courante = $fos_entrepriseCourante, fu.code_entreprise = ${fos_entrepriseCourante:0:3}, fu.pre_commande = $fos_typePreCommande, fu.code_zone_sec = z1.code_zone, fu.code_zone_frais = z2.code_zone, fu.code_zone_surgele = z3.code_zone, fu.etat = 'client'
    WHERE fu.username = '$fos_username'" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"
        exit 1
    fi
    echo "done."

    echo -ne "create clients precommande..."
    #echo "INSERT INTO client
    #(code_client , code_enseigne ,code_tarification , code_region, code_entreprise, code_meta_client, nom, responsable1, responsable2, adresse1, adresse2, code_postale, ville, telephone, fax, email, statut, regroupement_client, e_actif, promotions_compteur)
    #SELECT REPLACE( code_client, '-', '1-' ) , code_enseigne, code_tarification, code_region, '${fos_entrepriseCourante:0:3}1', '$meta_command', nom, responsable1, responsable2, adresse1, adresse2, code_postale, ville, telephone, fax, email, 'A', regroupement_client, 1, promotions_compteur
    #FROM client
    #WHERE code_client = '$client'" | $MYSQLCOMMAND $DBNAME >/dev/null

    #echo "INSERT INTO assortiment_client 
    #(code_client , valeur ,code_tarification)
    #SELECT REPLACE( code_client, '-', '1-' ) , valeur, code_tarification)
    #FROM assortiment_client
    #WHERE code_client = '$client'" | $MYSQLCOMMAND $DBNAME >/dev/null
    #if [ $? -ne 0 ] ; then
    #    echo -e "${red}SQL issue!!${resetColor}"
    #    exit 1
    #fi

    echo "INSERT INTO client
    (code_client , code_enseigne ,code_tarification, code_region, code_entreprise, code_meta_client, nom, responsable1, responsable2, adresse1, adresse2, code_postale, ville, telephone, fax, email, statut, regroupement_client, e_actif, promotions_compteur)
    SELECT REPLACE( code_client, '-', '2-' ) , code_enseigne, code_tarification, code_region, '${fos_entrepriseCourante:0:3}2', '$meta_command', nom, responsable1, responsable2, adresse1, adresse2, code_postale, ville, telephone, fax, email, 'A', regroupement_client, 1, promotions_compteur
    FROM client
    WHERE code_client = '$client'" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"

        echo "DELETE FROM fos_user WHERE meta = '$meta_command'" | $MYSQLCOMMAND $DBNAME >/dev/null
        if [ $? -ne 0 ] ; then
            echo -e "${red}SQL issue (trying to delete fos_user)!!${resetColor}"
        fi
        exit 1
    fi

    echo "INSERT INTO assortiment_client 
    (code_client , valeur ,code_assortiment, as400_assortiment, assortiment_courant, assortiment_nom)
    SELECT REPLACE( code_client, '-', '2-' ) , valeur, code_assortiment, as400_assortiment, assortiment_courant, assortiment_nom
    FROM assortiment_client
    WHERE code_client = '$client'" | $MYSQLCOMMAND $DBNAME >/dev/null
    if [ $? -ne 0 ] ; then
        echo -e "${red}SQL issue!!${resetColor}"

        echo "DELETE FROM client WHERE code_meta_client = '$meta_command'" | $MYSQLCOMMAND $DBNAME >/dev/null
        if [ $? -ne 0 ] ; then
            echo -e "${red}SQL issue (trying to delete fos_user)!!${resetColor}"
        fi

        echo "DELETE FROM fos_user WHERE meta = '$meta_command'" | $MYSQLCOMMAND $DBNAME >/dev/null
        if [ $? -ne 0 ] ; then
            echo -e "${red}SQL issue (trying to delete fos_user)!!${resetColor}"
        fi

        exit 1
    fi
done
