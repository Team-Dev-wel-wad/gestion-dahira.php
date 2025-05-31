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

    // Extract form data from $_POST and $_FILES
    $params = $_POST;
    $files = $_FILES;

    if (empty($params) && empty($files)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters or file required";
        echo json_encode($reponse);
        exit;
    }

    // Prepare data for insertion
    $data = [
        'nom_dahira' => isset($params['nom_dahira']) ? $params['nom_dahira'] : null,
        'adresse' => isset($params['adresse']) ? $params['adresse'] : null,
        'lieu' => isset($params['lieu']) ? $params['lieu'] : null,
        'statut' => isset($params['statut']) ? $params['statut'] : null,
        'description' => isset($params['description']) ? $params['description'] : null,

        'date_creation' => isset($params['date_creation']) && $params['date_creation'] ? $params['date_creation'] : null
    ];

    // Validate required fields
    $required_fields = ['nom_dahira', 'adresse', 'lieu', 'statut', 'description', 'date_creation'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $reponse["status"] = false;
            $reponse["erreur"] = "Le champ $field est requis";
            echo json_encode($reponse);
            exit;
        }
    }

    // Handle file upload
    $image_path = null;
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
    }

    // Build insert query
    $query = $table_query->dynamicInsert($data);
    // $reponse["query"] = $query;
    $resultat = $taf_config->get_db()->exec($query);

    if ($resultat) {
        // Get last inserted ID
        $last_id = $taf_config->get_db()->lastInsertId();
        $reponse["status"] = true;
        $reponse["data"] = array_merge(['id_dahira' => $last_id], $data);
    } else {
        // Clean up uploaded file if insert fails
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $reponse["status"] = false;
        $reponse["erreur"] = "Erreur lors de l'insertion";
    }
    echo json_encode($reponse);
} catch (\Throwable $th) {
    // Clean up uploaded file if error occurs
    if (isset($image_path) && file_exists($image_path)) {
        unlink($image_path);
    }
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();
    echo json_encode($reponse);
}
?>
