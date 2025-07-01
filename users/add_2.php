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

    // Hasher le mot de passe ou utiliser un par défaut
    $params['mot_de_passe'] = isset($params['mot_de_passe']) && !empty($params['mot_de_passe'])
        ? password_hash($params['mot_de_passe'], PASSWORD_DEFAULT)
        : password_hash('1234', PASSWORD_DEFAULT);

    $table_name = 'users';
    $table_query = new TableQuery($table_name);

    if (empty($params)) {
        echo json_encode(["status" => false, "erreur" => "Parameters required"]);
        exit;
    }

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
        'date_enregistrement' => date("Y-m-d H:i:s")
    ];

    // Validation des champs obligatoires
    $required_fields = ['nom_users', 'sexe', 'date_naissance', 'adresse', 'telephone', 'email', 'mot_de_passe', 'id_privilege', 'id_dahira'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            echo json_encode(["status" => false, "erreur" => "Le champ $field est requis"]);
            exit;
        }
    }

    // Validation email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => false, "erreur" => "L'email fourni n'est pas valide"]);
        exit;
    }

    // Validation téléphone
    if (!preg_match('/^[0-9]{9,}$/', $data['telephone'])) {
        echo json_encode(["status" => false, "erreur" => "Le numéro de téléphone doit contenir au moins 9 chiffres"]);
        exit;
    }

    // Insertion dans la table users
    $query = $table_query->dynamicInsert($data);
    $db = $taf_config->get_db();

    if ($db->exec($query)) {
        $last_id = $db->lastInsertId();
        $data["id_$table_name"] = $last_id;

        $reponse = ["status" => true, "data" => $data];

        // Vérifier s'il faut générer une carte
        if (!empty($params['generer_carte']) && in_array($params['generer_carte'], [true, 'true', 1, '1'], true)) {
            // Vérifie si une carte existe déjà pour ce membre
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM carte_membre WHERE id_users = :id_users");
            $checkStmt->bindParam(':id_users', $last_id);
            $checkStmt->execute();
            $carte_existe = $checkStmt->fetchColumn();

            if ($carte_existe > 0) {
                $reponse["carte_membre"] = "Une carte existe déjà pour cet utilisateur.";
            } else {
                $date_delivrance = date("Y-m-d");
                $date_expiration = date("Y-m-d", strtotime("+5 years"));

                $stmt = $db->prepare("
                    INSERT INTO carte_membre (date_delivrance, date_expiration, id_users)
                    VALUES (:date_delivrance, :date_expiration, :id_users)
                ");
                $stmt->bindParam(':date_delivrance', $date_delivrance);
                $stmt->bindParam(':date_expiration', $date_expiration);
                $stmt->bindParam(':id_users', $last_id);

                if ($stmt->execute()) {
                    $reponse["carte_membre"] = [
                        "date_delivrance" => $date_delivrance,
                        "date_expiration" => $date_expiration,
                        "id_users" => $last_id
                    ];
                } else {
                    $reponse["carte_membre"] = "Erreur lors de la création de la carte membre.";
                }
            }
        }
    } else {
        $reponse = ["status" => false, "erreur" => "Erreur d'insertion dans la table $table_name"];
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    echo json_encode(["status" => false, "erreur" => $th->getMessage()]);
}
?>
