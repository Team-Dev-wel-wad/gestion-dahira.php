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
    
    $table_query = new TableQuery('cotisation_mensuelle'); 
    
    
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
    if (!isset($data['verser'])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "'verser' key is required in data";
        echo json_encode($reponse);
        exit;
    }

   
    $query = "UPDATE cotisation_mensuelle SET verser = :verser {$condition}";

    
    file_put_contents('php://stderr', "Query: $query\n");

    $stmt = $taf_config->get_db()->prepare($query);
    $stmt->bindParam(':verser', $data['verser']);
    
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