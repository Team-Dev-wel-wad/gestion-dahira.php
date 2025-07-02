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

    if (empty($params) && empty($files)) {
        echo json_encode(["status" => false, "erreur" => "Données ou image requise"]);
        exit;
    }

    // Hasher le mot de passe
    $params['mot_de_passe'] = isset($params['mot_de_passe']) && !empty($params['mot_de_passe'])
        ? password_hash($params['mot_de_passe'], PASSWORD_DEFAULT)
        : password_hash('1234', PASSWORD_DEFAULT);

    // Nettoyer téléphone
    $params['telephone'] = preg_replace('/\D/', '', $params['telephone'] ?? '');

    $data = [
        'nom_users' => $params['nom_users'] ?? null,
        // 'id_genre' => $params['id_genre'] ?? null,
        'id_genre' => $params['id_genre'] ?? null,
        'date_naissance' => $params['date_naissance'] ?? null,
        'adresse' => $params['adresse'] ?? null,
        'telephone' => $params['telephone'] ?? null,
        'profession' => $params['profession'] ?? null,
        'statut' => $params['statut'] ?? null,
        'antecedents' => $params['antecedents'] ?? null,
        'email' => $params['email'] ?? null,
        'mot_de_passe' => $params['mot_de_passe'],
        'id_privilege' => $params['id_privilege'] ?? null,
        'id_dahira' => $params['id_dahira'] ?? null,
        'date_inscription' => date("Y-m-d H:i:s")
    ];

    $required_fields = ['nom_users', 'id_genre', 'date_naissance', 'statut', 'adresse', 'telephone', 'email', 'mot_de_passe', 'id_privilege', 'id_dahira'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            echo json_encode(["status" => false, "erreur" => "Le champ $field est requis"]);
            exit;
        }
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => false, "erreur" => "Email invalide"]);
        exit;
    }

    // if (!preg_match('/^[0-9]{9}$/', $data['telephone'])) {
    //     echo json_encode(["status" => false, "erreur" => "Téléphone invalide (9 chiffres attendus)"]);
    //     exit;
    // }
    // Nettoyage : supprimer tout sauf les chiffres
    $data['telephone'] = preg_replace('/\D/', '', $data['telephone']); // enlève +, espaces, tirets...

    // Vérifie que le numéro est raisonnablement long (minimum 9 chiffres, maximum 15 par précaution)
    if (strlen($data['telephone']) < 9 || strlen($data['telephone']) > 15) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Numéro de téléphone invalide (entre 9 et 15 chiffres attendus)";
        echo json_encode($reponse);
        exit;
    }


    // Image
    $image_path = null;
    if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = pathinfo($files['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('image_') . '.' . $ext;
        $image_path = $upload_dir . $filename;

        if (!move_uploaded_file($files['image']['tmp_name'], $image_path)) {
            echo json_encode(["status" => false, "erreur" => "Erreur upload image"]);
            exit;
        }

        $data['image'] = $image_path;
    }

    $db = $taf_config->get_db();
    $table_query = new TableQuery('users');
    $query = $table_query->dynamicInsert($data);

    if ($db->exec($query)) {
        $last_id = $db->lastInsertId();
        // Récupérer l’acronyme du dahira
        $stmt = $db->prepare("SELECT acronyme FROM dahira WHERE id_dahira = :id_dahira LIMIT 1");
        $stmt->bindParam(':id_dahira', $data['id_dahira']);
        $stmt->execute();
        $acronyme = $stmt->fetchColumn();

        if ($acronyme) {
            // Générer matricule (ex: NDSL51001)
            $matricule = strtoupper($acronyme) . str_pad($last_id, 4, "0", STR_PAD_LEFT);

            // Mettre à jour le user avec le matricule
            $update = $db->prepare("UPDATE users SET matricule = :matricule WHERE id_users = :id");
            $update->bindParam(':matricule', $matricule);
            $update->bindParam(':id', $last_id);
            $update->execute();

            $data['matricule'] = $matricule;
        }

        $data["id_users"] = $last_id;

        $reponse = ["status" => true, "data" => $data];

        // 🔁 Vérifier si une carte existe déjà
        $check = $db->prepare("SELECT COUNT(*) FROM carte_membre WHERE id_users = :id");
        $check->bindParam(':id', $last_id);
        $check->execute();
        if ($check->fetchColumn() > 0) {
            $reponse["carte_membre"] = "Une carte existe déjà pour cet utilisateur.";
        } else {
            $date_delivrance = date("Y-m-d");
            $date_expiration = date("Y-m-d", strtotime("+2 years"));

            $insertCarte = $db->prepare("
                INSERT INTO carte_membre (date_delivrance, date_expiration, id_users)
                VALUES (:d, :e, :id)
            ");
            $insertCarte->bindParam(':d', $date_delivrance);
            $insertCarte->bindParam(':e', $date_expiration);
            $insertCarte->bindParam(':id', $last_id);

            if ($insertCarte->execute()) {
                $reponse["carte_membre"] = [
                    "date_delivrance" => $date_delivrance,
                    "date_expiration" => $date_expiration,
                    "id_users" => $last_id
                ];
            } else {
                $reponse["carte_membre"] = "Erreur création carte membre.";
            }
        }
    } else {
        if ($image_path && file_exists($image_path)) unlink($image_path);
        $reponse = ["status" => false, "erreur" => "Échec d'insertion utilisateur"];
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    if (isset($image_path) && file_exists($image_path)) unlink($image_path);
    echo json_encode(["status" => false, "erreur" => $th->getMessage()]);
}
