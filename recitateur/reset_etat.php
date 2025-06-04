<?php
use Taf\TafAuth;
use Taf\TableQuery;

try {
    ini_set('display_errors', 0);
    error_reporting(0);

    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';

    header('Content-Type: application/json');

    $taf_auth = new TafAuth();
    $auth_reponse = $taf_auth->check_auth($reponse);
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        exit;
    }

    if (empty($params)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters required";
        echo json_encode($reponse);
        exit;
    }

    $condition = json_decode(json_encode($params), true);
    if (empty($condition) || !isset($condition['id_dahira'])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Invalid or missing id_dahira";
        echo json_encode($reponse);
        exit;
    }

    // Construire la clause WHERE
    $conditions = [];
    $db = $taf_config->get_db();
    $conditions[] = "id_dahira = " . $db->quote($condition['id_dahira']);
    if (isset($condition['id_users'])) {
        $conditions[] = "id_users = " . $db->quote($condition['id_users']);
    }

    $condition_sql = implode(' AND ', $conditions);
    $query = "UPDATE recitateur SET etat = 'non lu' WHERE $condition_sql";
    $resultat = $db->exec($query);

    if ($resultat !== false) {
        $reponse["status"] = true;
        $reponse["message"] = "Tous les états ont été mis à 'non lu'";
    } else {
        $reponse["status"] = false;
        $reponse["erreur"] = "Échec de la mise à jour des états";
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();
    header('Content-Type: application/json');
    echo json_encode($reponse);
}