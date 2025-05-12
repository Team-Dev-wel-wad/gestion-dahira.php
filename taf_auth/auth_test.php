<?php

use Taf\TafAuth;
use Taf\TafConfig;

try {
    require '../TafConfig.php';
    require './TafAuth.php';
    $taf_config = new TafConfig();
    $taf_auth= new TafAuth();
    $taf_config->allow_cors();

    $taf_auth->check_auth($reponse);

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();

    echo json_encode($reponse);
}
