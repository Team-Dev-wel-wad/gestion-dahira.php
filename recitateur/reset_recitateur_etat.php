<?php
use Taf\TableQuery;

try {
    // Désactiver l'affichage des erreurs
    ini_set('display_errors', 0);
    error_reporting(0);

    require './config.php';
    require '../TableQuery.php';

    // Définir le type de contenu JSON
    header('Content-Type: application/json');

    $table_query = new TableQuery('recitateur');

    // Requête pour mettre à jour tous les récitateurs
    $query = "UPDATE recitateur SET etat = 'non lu'";
    $resultat = $taf_config->get_db()->exec($query);

    $reponse = [];
    if ($resultat !== false) {
        $reponse["status"] = true;
        $reponse["message"] = "Tous les états ont été mis à jour à 'non lu'";
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