<?php
use Taf\TafAuth;
use Taf\TableQuery;

try {
    require './config.php';
    require '../TableQuery.php';
    require '../taf_auth/TafAuth.php';

    $taf_auth = new TafAuth();
    // Toutes les actions nécessitent une authentification
    $auth_reponse = $taf_auth->check_auth();
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        die;
    }

    // Récupérer les paramètres envoyés via POST
    $params = $_POST;

    // Valeur par défaut du mot de passe si non fourni
    if (!isset($params['mot_de_passe']) || empty($params['mot_de_passe'])) {
        $params['mot_de_passe'] = password_hash('1234', PASSWORD_DEFAULT); // Utilisation de password_hash pour une sécurité accrue
    } else {
        $params['mot_de_passe'] = password_hash($params['mot_de_passe'], PASSWORD_DEFAULT); // Toujours hasher le mot de passe
    }

    // Définir la table cible (par exemple 'users' en fonction des champs)
    $table_name = 'users'; // Ajustez selon le nom réel de votre table
    $table_query = new TableQuery($table_name);

    // Vérifier si des paramètres sont présents
    if (empty($params)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Parameters required";
        echo json_encode($reponse);
        exit;
    }

    // Préparer les données avec les champs reçus
    $data = [
        'nom_users' => isset($params['nom_users']) ? $params['nom_users'] : null,
        'sexe' => isset($params['sexe']) ? $params['sexe'] : null,
        'date_naissance' => isset($params['date_naissance']) ? $params['date_naissance'] : null,
        'adresse' => isset($params['adresse']) ? $params['adresse'] : null,
        'telephone' => isset($params['telephone']) ? $params['telephone'] : null,
        'profession' => isset($params['profession']) ? $params['profession'] : null,
        'antecedents' => isset($params['antecedents']) ? $params['antecedents'] : null,
        'email' => isset($params['email']) ? $params['email'] : null,
        'mot_de_passe' => $params['mot_de_passe'], // Déjà hashé
        'id_privilege' => isset($params['id_privilege']) ? $params['id_privilege'] : null,
        'id_dahira' => isset($params['id_dahira']) ? $params['id_dahira'] : null,
        'date_inscription' => date("Y-m-d H:i:s") // Ajout de la date courante
    ];

    // Valider les champs requis
    $required_fields = ['nom_users', 'sexe', 'date_naissance', 'adresse', 'telephone', 'email', 'mot_de_passe', 'id_privilege', 'id_dahira'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $reponse["status"] = false;
            $reponse["erreur"] = "Le champ $field est requis";
            echo json_encode($reponse);
            exit;
        }
    }

    // Valider le format de l'email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $reponse["status"] = false;
        $reponse["erreur"] = "L'email fourni n'est pas valide";
        echo json_encode($reponse);
        exit;
    }

    // Valider le téléphone (exemple : au moins 9 chiffres)
    if (!preg_match('/^[0-9]{9,}$/', $data['telephone'])) {
        $reponse["status"] = false;
        $reponse["erreur"] = "Le numéro de téléphone doit contenir au moins 9 chiffres";
        echo json_encode($reponse);
        exit;
    }

    // Construire la requête d'insertion
    $query = $table_query->dynamicInsert($data);
    // $reponse["query"] = $query; // Décommentez pour débogage

    // Exécuter la requête
    if ($taf_config->get_db()->exec($query)) {
        $reponse["status"] = true;
        $last_id = $taf_config->get_db()->lastInsertId();
        $data["id_$table_name"] = $last_id; // Ajouter l'ID généré
        $reponse["data"] = $data;
    } else {
        $reponse["status"] = false;
        $reponse["erreur"] = "Erreur d'insertion dans la table $table_name";
    }

    echo json_encode($reponse);
} catch (\Throwable $th) {
    $reponse["status"] = false;
    $reponse["erreur"] = $th->getMessage();
    echo json_encode($reponse);
}
?>