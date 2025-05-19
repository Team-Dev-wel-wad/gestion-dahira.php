<?php

namespace Taf;

use Taf\TafAuth;

try {
    require './config.php';
    require '../taf_auth/TafAuth.php';
    
    // Authenticate the user
    $taf_auth = new TafAuth();
    $auth_reponse = $taf_auth->check_auth($reponse);
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        die;
    }

    // Initialize TableQuery
    $table_query = new TableQuery('cotisation_mensuelle');

    // Get the parameters sent via POST
    $params = json_decode(file_get_contents("php://input"), true);

    if (empty($params) || !isset($params["condition"]) || !isset($params["data"])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters required";
        echo json_encode($reponse);
        exit;
    }

    // Create the condition for the update
    $condition = $table_query->dynamicCondition(json_decode($params["condition"]), '=');

    // Check if 'verser' is provided in data
    $data = json_decode($params["data"], true);
    if (!isset($data['verser'])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "'verser' key is required in data";
        echo json_encode($reponse);
        exit;
    }

    // Prepare the update query for only the 'verser' column
    $query = $table_query->dynamicUpdate(['verser' => $data['verser']], $condition);

    // Execute the query
    $resultat = $taf_config->get_db()->exec($query);

    if ($resultat) {
        $reponse["status"] = true;
    } else {
        $reponse["status"] = false;
        $reponse["erreur"] = "Error during update or no modification made";
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();
    echo json_encode($reponse);
}