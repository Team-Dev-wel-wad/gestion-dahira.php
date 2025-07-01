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

    // Extract form data from $_POST and $_FILES
    $params = $_POST;
    $files = $_FILES;

    if (empty($params) && empty($files)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters or file required";
        echo json_encode($reponse);
        exit;
    }

    // Prepare data for insertion with password hashing
    if (!isset($params['mot_de_passe']) || empty($params['mot_de_passe'])) {
        $params['mot_de_passe'] = password_hash('1234', PASSWORD_DEFAULT); // Default password if not provided
    } else {
        $params['mot_de_passe'] = password_hash($params['mot_de_passe'], PASSWORD_DEFAULT); // Hash provided password
    }

    $data = [
        'nom_users' => isset($params['nom_users']) ? $params['nom_users'] : null,
        'sexe' => isset($params['sexe']) ? $params['sexe'] : null,
        'date_naissance' => isset($params['date_naissance']) ? $params['date_naissance'] : null,
        'adresse' => isset($params['adresse']) ? $params['adresse'] : null,
        'telephone' => isset($params['telephone']) ? $params['telephone'] : null,
        'profession' => isset($params['profession']) ? $params['profession'] : null,
        'statut' => isset($params['statut']) ? $params['statut'] : null,
        'antecedents' => isset($params['antecedents']) ? $params['antecedents'] : null,
        'email' => isset($params['email']) ? $params['email'] : null,
        'mot_de_passe' => $params['mot_de_passe'], // Already hashed
        'id_privilege' => isset($params['id_privilege']) ? $params['id_privilege'] : null,
        'id_dahira' => isset($params['id_dahira']) ? $params['id_dahira'] : null,
        'date_inscription' => date("Y-m-d H:i:s") // Current timestamp
    ];

    // Validate required fields
    $required_fields = ['nom_users', 'sexe', 'date_naissance','statut', 'adresse', 'telephone', 'email', 'mot_de_passe', 'id_privilege', 'id_dahira'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $reponse["status"] = false;
            $reponse["erreur"] = "Le champ $field est requis";
            echo json_encode($reponse);
            exit;
        }
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "L'email fourni n'est pas valide";
        echo json_encode($reponse);
        exit;
    }

    // Validate telephone (at least 9 digits)
    if (!preg_match('/^[0-9]{9,}$/', $data['telephone'])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Le numéro de téléphone doit contenir au moins 9 chiffres";
        echo json_encode($reponse);
        exit;
    }

    // Handle profile image upload (optional)
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
        $data['image'] = $image_path; // Add image path to data if uploaded
    }

    // Définir la table cible
    $table_name = 'users';
    $table_query = new TableQuery($table_name);

    // Build insert query
    $query = $table_query->dynamicInsert($data);
    // $reponse["query"] = $query; // Uncomment for debugging

    // Execute the query
    if ($taf_config->get_db()->exec($query)) {
        // Get last inserted ID
        $last_id = $taf_config->get_db()->lastInsertId();
        $reponse["status"] = true;
        $reponse["data"] = array_merge(['id_users' => $last_id], $data);
    } else {
        // Clean up uploaded file if insert fails
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $reponse["status"] = false;
        $reponse["erreur"] = "Erreur lors de l'insertion dans la table $table_name";
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