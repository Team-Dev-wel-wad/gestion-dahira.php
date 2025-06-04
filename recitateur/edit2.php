<?php
use Taf\TafAuth;
use Taf\TableQuery;

try {
    // Désactiver l'affichage des erreurs en production
    ini_set('display_errors', 0);
    error_reporting(0);

    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';

    // Définir le type de contenu JSON
    header('Content-Type: application/json');

    $taf_auth = new TafAuth();
    // Vérification de l'authentification
    $auth_reponse = $taf_auth->check_auth($reponse);
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        exit;
    }

    $table_query = new TableQuery($table_name);

    // Validation des paramètres
    if (empty($params)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters required";
        echo json_encode($reponse);
        exit;
    }

    if (!isset($params["data"]) || !isset($params["condition"])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Missing data or condition parameters";
        echo json_encode($reponse);
        exit;
    }

    // Décodage des paramètres JSON
    $data = json_decode($params["data"], true);
    $condition = json_decode($params["condition"], true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($data) || empty($condition)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Invalid JSON format in data or condition";
        echo json_encode($reponse);
        exit;
    }

    // Générer et exécuter la requête de mise à jour
    $condition_sql = $table_query->dynamicCondition($condition, '=');
    $query = $table_query->dynamicUpdate($data, $condition_sql);
    $resultat = $taf_config->get_db()->exec($query);

    if ($resultat !== false) {
        $reponse["status"] = true;
        $reponse["message"] = "Update successful";
    } else {
        $reponse["status"] = false;
        $reponse["erreur"] = "Update failed or no changes made";
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();
    header('Content-Type: application/json');
    echo json_encode($reponse);
}