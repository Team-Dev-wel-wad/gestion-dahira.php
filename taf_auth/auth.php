<?php

use Taf\TafConfig;
use Taf\TafAuth;

try {
    require '../TafConfig.php';
    require './TafAuth.php';
    $taf_auth = new TafAuth();
    $taf_config = new TafConfig();
    $taf_config->allow_cors();

    $params = $_POST;
    if (file_get_contents('php://input') == "") {
        $params = [];
    } else {
        $params = json_decode(file_get_contents('php://input'), true);
    }
    // var_dump($params);
    // die;
    $reponse["params"] = $params;

    if (count($params) == 0) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters required";
        echo json_encode($reponse);
        exit;
    }
    // $email = addslashes($params["email"]);
    // $mot_de_passe = addslashes($params["mot_de_passe"]);

    //  $query = "select * from users where email ='$email' and mot_de_passe=md5('$mot_de_passe') ";

    // $resultat = $taf_config->get_db()->query($query)->fetch(PDO::FETCH_ASSOC);
    // if ($resultat) {
    //     $reponse["status"] = true;
    //     $reponse["data"] = $taf_auth->get_token($resultat);
    // } else {
    //     $reponse["status"] = false;
    // }
    $email = $params["email"];
    $mot_de_passe = $params["mot_de_passe"];

    $stmt = $taf_config->get_db()->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultat && password_verify($mot_de_passe, $resultat['mot_de_passe'])) {
        $reponse["status"] = true;
        $reponse["data"] = $taf_auth->get_token($resultat);
    } else {
        $reponse["status"] = false;
        $reponse["erreur"] = "Identifiants invalides";
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();

    echo json_encode($reponse);
}
