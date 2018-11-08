#!/bin/bash
SERVICE='sogedial:executeDatabaseSetup'

for i in "$@"
do
        # Check if process is running
        if ps -ax|grep -v grep|grep $SERVICE > /dev/null
        then
                echo "$SERVICE is already running ----------------------"
        else
                echo "Handling region : $i ----------------------"
                cd /home/integration/import.commande.com
                php /home/integration/import.commande.com/app/console sogedial:executeDatabaseSetup --env=prod $i
                echo "region : $i ok ----------------------------"
        fi
done