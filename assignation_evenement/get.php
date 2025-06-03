<?php

use Taf\TafAuth;
use Taf\TableQuery;

try {
    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';
    $taf_auth = new TafAuth();
    /* 
        $params
        contient tous les parametres envoyés par la methode POST
     */
    // toutes les actions nécéssitent une authentification
    $auth_reponse=$taf_auth->check_auth();
    if ($auth_reponse["status"] == false && count($params)==0) {
        echo json_encode($auth_reponse);
        die;
    }
    
    $table_query=new TableQuery($table_name);

    $condition=$table_query->dynamicCondition($params,"=");
    // $reponse["condition"]=$condition;
    $query="select *from $table_name ".$condition;
    $reponse["data"] = $taf_config->get_db()->query(
        "
     SELECT 
    a.*, 
    u.*, 
    e.*, 
    c.*,
    (c.montant_categorie - a.montant_verser) AS montant_restant, 
    ROUND((a.montant_verser / c.montant_categorie) * 100, 2) AS pourcentage_verse 
FROM 
    assignation_evenement a 
JOIN 
    users u ON a.id_users = u.id_users 
JOIN 
    evenement e ON a.id_evenement = e.id_evenement
JOIN 
    categorie_assignation c ON a.id_categorie_assignation = c.id_categorie_assignation
ORDER BY 
    e.date_evenement DESC,               -- Événement le plus récent en premier
    c.id_categorie_assignation ASC,      -- Groupement logique par catégorie
    pourcentage_verse DESC;              -- Tri par pourcentage décroissant



        "
        )->fetchAll(PDO::FETCH_ASSOC);
    $reponse["status"] = true;

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();

    echo json_encode($reponse);
}

?>