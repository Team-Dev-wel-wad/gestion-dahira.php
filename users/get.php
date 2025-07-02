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
    $auth_reponse = $taf_auth->check_auth();
    if ($auth_reponse["status"] == false && count($params) == 0) {
        echo json_encode($auth_reponse);
        die;
    }

    $table_query = new TableQuery($table_name);

    $condition = $table_query->dynamicCondition($params, "=");
    // $reponse["condition"]=$condition;
    // $query="select u*,p.* from $table_name ".$condition;
    // $query = "SELECT u.*, p.* 
    //           FROM users u 
    //           JOIN privilege p ON u.id_privilege = p.id_privilege 
    //           WHERE u.statut = 'actif' 
    //           ORDER BY u.nom_users ASC";
    $query = "SELECT 
  u.*, 
  p.*, 
  g.type_genre, 
  c.date_delivrance, 
  c.date_expiration
FROM users u
JOIN privilege p ON u.id_privilege = p.id_privilege
LEFT JOIN genre g ON u.id_genre = g.id_genre
LEFT JOIN carte_membre c ON u.id_users = c.id_users
LEFT JOIN dahira d ON u.id_dahira = d.id_dahira

WHERE u.statut = 'actif'
ORDER BY u.nom_users ASC;
";

    $reponse["data"] = $taf_config->get_db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
    $reponse["status"] = true;

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();

    echo json_encode($reponse);
}
