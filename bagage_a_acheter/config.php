<?php

use Taf\TafAuth;

try {
    require '../TafConfig.php';
    $reponse = array();
    $table_name = "bagage_a_acheter";
    $taf_config = new \Taf\TafConfig();
    $taf_config->allow_cors();
    if (file_get_contents('php://input')=="") {
        $params=[];
    } else {
        $params=json_decode(file_get_contents('php://input'),true);
    }    
} catch (\Throwable $th) {
    echo "<h1>" . $th->getMessage() . "</h1>";
}
