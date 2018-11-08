<?php

//
// Accès direct aux BDD en utilisant l'extension mysqli (c'est donc indépendant de la connexion PDO utilisée par Doctrine)
//
// Utilisation :
//
// 1) création et destruction de la connexion automatique (vous avez besoin de faire une seule requête):
//
//    $rows=$sql->query("QUERY;");
//
// 2) création et destruction de la connexion automatique (vous avez besoin de faire plusieurs requêtes liées, mais seulement les résultats
//    de la dernière requête vous intéressent):
//
//    $rows=$sql->multi_query("QUERY1;QUERY2;QUERY3;");
//
// 3) connexion manuelle, plusieurs requêtes (simples ou multiples)
//
//    $sql->connect();
//    $rows1=$sql->multi_query("QUERY1;", false);
//    $rows2=$sql->query("QUERY2;", false);
//    $sql->disconnect();
//
// Gestion des erreurs:
//    En cas de succès, $sql->error==="". Sinon, cette variable contient une description du problème.
//

namespace Sogedial\SiteBundle\Service;

use Mysqli;

class SimpleMySQLService
{
    private $database_host;
    private $database_port;
    private $database_name;
    private $database_user;
    private $database_password;

    private $connection =  false;

    public $error = "";

    public function __construct($database_host, $database_port, $database_name, $database_user, $database_password)
    {
        $this->database_host = $database_host;
        $this->database_port = $database_port;
        $this->database_name = $database_name;
        $this->database_user = $database_user;
        $this->database_password = $database_password;
    }

    // polyfill
    // Couche de compatibilité avec PHP < 5.3 ou absence de l'extension mysqlnd
    private function fetch_all($result, $resulttype = MYSQLI_NUM)
    {
        // en fait, on ne l'utilise jamais
        if ( false && method_exists('mysqli_result', 'fetch_all')) {
            $res = $result->fetch_all($resulttype);
        } else {
            for ($res = array(); $tmp = $result->fetch_array($resulttype);) $res[] = $tmp;
        }

        return $res;
    }    

    // retourne "" en cas de succès, une chaîne de caractères comportant le message d'erreur sinon
    public function connect()
    {
        if ($this->connection !== false)
        {
            // déjà connecté
            $this->error="Already connected";
            return;
        }

        // créer une connexion
        $this->connection = new Mysqli($this->database_host, $this->database_user, $this->database_password, $this->database_name);

        // vérification de la connexion
        if ($this->connection->connect_error)
        {
            $this->connection = false;
            $this->error="Connection failed: " . $this->connection->connect_error;
            return;
        }

        $this->error="";

        $this->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'", false);  // peut lui-même avoir une erreur
    }

    public function query($query, $connect = true)
    {
        if ($connect === true)
        {
            $this->connect();
            if ($this->connection === false)
            {
                return array();
            }
        }

        if ($this->connection === false)
        {
            $this->error="Not connected.";
            return array();
        }

        $result = $this->connection->query($query);

        $return_value = array();

        if($result === false)
        {
            // il faut accéder à l'erreur avant disconnect()
            $this->error = 'SQL query failed: ' . $query . ' ; Error '.$this->connection->errno.': ' . $this->connection->error;
            if ($connect === true)
            {
                $this->disconnect();
            }
            return array();
        }
        else if ($result !== true)  // uniquement pour les commandes qui retourne quelque chose
        {
            // tableau contenant les lignes, chaque ligne étant un tableau associatif avec la clef = le nom des colonnes
            $return_value = $this->fetch_all($result, MYSQLI_ASSOC);
        }
        $this->error = "";

        if ($connect === true)
        {
            $this->disconnect();
        }

        return $return_value;

        /*
        if ($result->num_rows > 0) {
            $rs->data_seek(0);
            while($row = $result->fetch_assoc()) {
                echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
            }
        } else {
            echo "0 results";
        }
        */
    }

    // ce wrapper exécute plusieurs requêtes contenues dans la même chaîne de caractères
    // il ne retourne que le résultat du dernier
    public function multi_query($query, $connect = true)
    {
        if ($connect === true)
        {
            $this->connect();
            if ($this->connection === false)
            {
                return array();
            }
        }

        if ($this->connection === false)
        {
            $this->error="Not connected.";
            return array();
        }

        $result = $this->connection->multi_query($query);

        $return_value = array();

        if($result === false)
        {
            if ($connect === true)
            {
                $this->disconnect();
            }
            $this->error = 'SQL query failed: ' . $query . ' ; Error '.$this->connection->errno.': ' . $this->connection->error;
            return array();
        }
        else
        {
            // tableau contenant les lignes, chaque ligne étant un tableau associatif avec la clef = le nom des colonnes

            // on avance jusqu'au dernier résultat
            while ($this->connection->more_results() && $this->connection->next_result()) {};

            if ($this->connection->errno)
            {
                // il faut accéder à l'erreur avant disconnect()
                $this->error = 'SQL query failed: ' . $query . ' ; Error '.$this->connection->errno.': ' . $this->connection->error;
                if ($connect === true)
                {
                    $this->disconnect();
                }
                return array();
            }
            else
            {
                if ($last_result = $this->connection->store_result())
                {
                    $return_value = $this->fetch_all($last_result, MYSQLI_ASSOC);
                    $last_result->free();
                }
            }
        }
        $this->error="";

        if ($connect === true)
        {
            $this->disconnect();
        }

        return $return_value;
    }

    public function disconnect()
    {
        if ($this->connection === false)
        {
            // pas connecté
            $this->error="Already disconnected";
            return;
        }
        // déconnexion
        $this->connection->close();
        $this->connection = false;
        $this->error="";
    }

    public function bind()
    {
        // TODO plus tard si besoin
        // // prepare and bind
        // $stmt = $conn->prepare("INSERT INTO clients (firstname, age) VALUES (?, ?)");
        // $stmt->bind_param("si", $firstname, $age);

        // // set parameters and execute
        // $firstname = "John";
        // $age = 40;
        // $stmt->execute();

        // $firstname = "Bill";
        // $age = 70;
        // $stmt->execute();

        // $stmt->close();
    }

}