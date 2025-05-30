<?php

use Taf\TafAuth;
use Taf\TableQuery;

try {
    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';
    
    $taf_auth = new TafAuth();
    
   
    $auth_reponse = $taf_auth->check_auth($reponse);
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        die;
    }
    
    $table_query = new TableQuery('assignation_evenement'); 
    
    
    $params = json_decode(file_get_contents("php://input"), true);

    if (empty($params) || !isset($params["condition"]) || !isset($params["data"])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters required";
        echo json_encode($reponse);
        exit;
    }

    
    $condition = $table_query->dynamicCondition(json_decode($params["condition"]), '=');
    
    
    file_put_contents('php://stderr', "Condition: $condition\n");

    
    $data = json_decode($params["data"], true);
    if (!isset($data['montant_verser'])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "'montant_verser' key is required in data";
        echo json_encode($reponse);
        exit;
    }

   
    $query = "UPDATE assignation_evenement SET montant_verser = :montant_verser {$condition}";

    
    file_put_contents('php://stderr', "Query: $query\n");

    $stmt = $taf_config->get_db()->prepare($query);
    $stmt->bindParam(':montant_verser', $data['montant_verser']);
    
    $resultat = $stmt->execute();

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