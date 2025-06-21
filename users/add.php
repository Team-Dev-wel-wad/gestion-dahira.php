<?php

use Taf\TafAuth;
use Taf\TableQuery;

try {
    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';
    $taf_auth = new TafAuth();
    // toutes les actions nécéssitent une authentification
    $auth_reponse = $taf_auth->check_auth();
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        die;
    }
    // Valeur par défaut du mot de passe si non fourni
    if (!isset($params['mot_de_passe']) || empty($params['mot_de_passe'])) {
        $params['mot_de_passe'] = md5(123); // ou password_hash('1234', PASSWORD_DEFAULT)
    } else {
        $params['mot_de_passe'] = md5($params['mot_de_passe']); // hash toujours
    }

    $table_query = new TableQuery($table_name);
    /* 
        $params
        contient tous les parametres envoyés par la methode POST
     */

    if (empty($params)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters required";
        echo json_encode($reponse);
        exit;
    }
    // pour charger l'heure courante
    // $params["date_enregistrement"]=date("Y-m-d H:i:s");
    $query = $table_query->dynamicInsert($params);
    // $reponse["query"]=$query;
    if ($taf_config->get_db()->exec($query)) {
        $reponse["status"] = true;
        $params["id_$table_name"] = $taf_config->get_db()->lastInsertId();
        $reponse["data"] = $params;
    } else {
        $reponse["status"] = false;
        $reponse["erreur"] = "Erreur d'insertion à la base de ";
    }
    echo json_encode($reponse);
} catch (\Throwable $th) {

    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();

    echo json_encode($reponse);
}
