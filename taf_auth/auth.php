<?php

use Taf\TafConfig;
use Taf\TafAuth;

try {
    require '../TafConfig.php';
    require './TafAuth.php';

    $taf_auth = new TafAuth();
    $taf_config = new TafConfig();

    // Autoriser les requêtes cross-origin (CORS)
    $taf_config->allow_cors();

    // Récupération des données du corps de la requête
    $params = $_POST;
    if (file_get_contents('php://input') !== "") {
        $params = json_decode(file_get_contents('php://input'), true);
    }

    $reponse["params"] = $params;

    if (count($params) == 0) {
        echo json_encode([
            "status" => false,
            "erreur" => "Parameters required"
        ]);
        exit;
    }

    // Nettoyage des paramètres
    $email = $params["email"] ?? '';
    $mot_de_passe = $params["mot_de_passe"] ?? '';

    if (empty($email) || empty($mot_de_passe)) {
        echo json_encode([
            "status" => false,
            "erreur" => "Email et mot de passe requis"
        ]);
        exit;
    }

    // Préparer la requête SQL
    $stmt = $taf_config->get_db()->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification du mot de passe en MD5
    if ($resultat && $resultat['mot_de_passe'] === md5($mot_de_passe)) {
        $reponse["status"] = true;
        $reponse["data"] = $taf_auth->get_token($resultat);
    } else {
        $reponse["status"] = false;
        $reponse["erreur"] = "Identifiants invalides";
    }

    echo json_encode($reponse);

} catch (\Throwable $th) {
    echo json_encode([
        "status" => false,
        "erreur" => $th->getMessage()
    ]);
}
