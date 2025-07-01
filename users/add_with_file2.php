<?php
use Taf\TafAuth;
use Taf\TableQuery;

try {
    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';

    $taf_auth = new TafAuth();
    $auth_reponse = $taf_auth->check_auth();
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        die;
    }

    $params = $_POST;
    $files = $_FILES;
    $image_path = null;

    // Gestion du mot de passe
    $params['mot_de_passe'] = isset($params['mot_de_passe']) && !empty($params['mot_de_passe'])
        ? password_hash($params['mot_de_passe'], PASSWORD_DEFAULT)
        : password_hash('1234', PASSWORD_DEFAULT);

    $table_query = new TableQuery('users');

    // Validation des paramètres obligatoires
    $data = [
        'nom_users' => $params['nom_users'] ?? null,
        'sexe' => $params['sexe'] ?? null,
        'date_naissance' => $params['date_naissance'] ?? null,
        'adresse' => $params['adresse'] ?? null,
        'telephone' => $params['telephone'] ?? null,
        'profession' => $params['profession'] ?? null,
        'antecedents' => $params['antecedents'] ?? null,
        'email' => $params['email'] ?? null,
        'mot_de_passe' => $params['mot_de_passe'],
        'id_privilege' => $params['id_privilege'] ?? null,
        'id_dahira' => $params['id_dahira'] ?? null,
        'date_inscription' => date("Y-m-d H:i:s")
    ];

    $required_fields = ['nom_users', 'sexe', 'date_naissance', 'adresse', 'telephone', 'email', 'mot_de_passe', 'id_privilege', 'id_dahira'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            echo json_encode(["status" => false, "erreur" => "Le champ $field est requis"]);
            exit;
        }
    }

    // Validation email et téléphone
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => false, "erreur" => "Email invalide"]);
        exit;
    }
    if (!preg_match('/^[0-9]{9,}$/', $data['telephone'])) {
        echo json_encode(["status" => false, "erreur" => "Numéro de téléphone invalide"]);
        exit;
    }

    // Upload de la photo (optionnelle)
    if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $extension = pathinfo($files['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('user_') . '.' . $extension;
        $image_path = $upload_dir . $filename;

        if (!move_uploaded_file($files['photo']['tmp_name'], $image_path)) {
            echo json_encode(["status" => false, "erreur" => "Échec de l’upload de l’image"]);
            exit;
        }

        $data['photo'] = $image_path;
    }

    // Insertion du membre
    $query = $table_query->dynamicInsert($data);
    $db = $taf_config->get_db();

    if (!$db->exec($query)) {
        if ($image_path && file_exists($image_path)) unlink($image_path);
        echo json_encode(["status" => false, "erreur" => "Erreur d'insertion"]);
        exit;
    }

    $last_id = $db->lastInsertId();
    $data["id_users"] = $last_id;

    $reponse = ["status" => true, "data" => $data];

    // 👉 Génération de la carte si demandé
    if (!empty($params['generer_carte']) && in_array($params['generer_carte'], ['1', 1, 'true', true], true)) {
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM carte_membre WHERE id_users = :id");
        $checkStmt->bindParam(':id', $last_id);
        $checkStmt->execute();
        $existe = $checkStmt->fetchColumn();

        if ($existe > 0) {
            $reponse["carte_membre"] = "Une carte existe déjà pour cet utilisateur.";
        } else {
            $date_delivrance = date("Y-m-d");
            $date_expiration = date("Y-m-d", strtotime("+5 years"));

            $stmt = $db->prepare("
                INSERT INTO carte_membre (date_delivrance, date_expiration, id_users)
                VALUES (:d, :e, :id)
            ");
            $stmt->bindParam(':d', $date_delivrance);
            $stmt->bindParam(':e', $date_expiration);
            $stmt->bindParam(':id', $last_id);

            if ($stmt->execute()) {
                $reponse["carte_membre"] = [
                    "date_delivrance" => $date_delivrance,
                    "date_expiration" => $date_expiration,
                    "id_users" => $last_id
                ];
            } else {
                $reponse["carte_membre"] = "Erreur lors de la création de la carte.";
            }
        }
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    if (isset($image_path) && file_exists($image_path)) unlink($image_path);
    echo json_encode(["status" => false, "erreur" => $th->getMessage()]);
}
