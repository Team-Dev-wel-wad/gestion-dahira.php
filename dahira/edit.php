<?php
use Taf\TafAuth;
use Taf\TableQuery;

try {
    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';
    $taf_auth = new TafAuth();
    // Toutes les actions nécessitent une authentification
    $auth_reponse = $taf_auth->check_auth($reponse);
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        die;
    }

    $table_query = new TableQuery('dahira');
    $params = $_POST;
    $files = $_FILES;

    if (empty($params) && empty($files)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters or file required";
        echo json_encode($reponse);
        exit;
    }

    // Decode params
    $condition = json_decode($params["condition"], true);
    $data = json_decode($params["data"], true);
    $old_image_path = isset($params["old_image"]) ? $params["old_image"] : null;

    // Handle file upload
    $image_path = $old_image_path;
    if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $image_name = uniqid() . '_' . basename($files['image']['name']);
        $image_path = $upload_dir . $image_name;
        if (!move_uploaded_file($files['image']['tmp_name'], $image_path)) {
            $reponse["status"] = false;
            $reponse["erreur"] = "Erreur lors de l'upload de l'image";
            echo json_encode($reponse);
            exit;
        }
        $data['image'] = $image_path;
        if ($old_image_path && file_exists($old_image_path) && $old_image_path !== $image_path) {
            unlink($old_image_path);
        }
    }

    // Build condition for update
    $condition_query = $table_query->dynamicCondition($condition, '=');
    // Build update query
    $query = $table_query->dynamicUpdate($data, $condition_query);
    // $reponse["query"] = $query;
    $resultat = $taf_config->get_db()->exec($query);

    if ($resultat) {
        $reponse["status"] = true;
        $reponse["data"] = $data;
    } else {
        if ($image_path !== $old_image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $reponse["status"] = false;
        $reponse["erreur"] = "Erreur! ou pas de modification";
    }
    echo json_encode($reponse);
} catch (\Throwable $th) {
    if (isset($image_path) && $image_path !== $old_image_path && file_exists($image_path)) {
        unlink($image_path);
    }
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();
    echo json_encode($reponse);
}
?>
