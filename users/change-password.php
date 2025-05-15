<?php
header('Content-Type: application/json; charset=utf-8');
use Taf\TafAuth;

class TableQuery {
    private $table;
    private $db;

    public function __construct($table, $db) {
        $this->table = $table;
        $this->db = $db;
    }

    public function dynamicCondition($conditions, $operator = '=') {
        $conditionParts = [];
        foreach ($conditions as $key => $value) {
            $conditionParts[] = "$key $operator :cond_$key";
        }
        return implode(' AND ', $conditionParts);
    }

    public function dynamicUpdate($data, $condition) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
        }
        $setClauseString = implode(', ', $setClause);
        return "UPDATE {$this->table} SET $setClauseString WHERE $condition";
    }

    public function get_db() {
        return $this->db;
    }
}

try {
    require './config.php';
    require '../taf_auth/TafAuth.php';

    $taf_auth = new TafAuth();
    $auth_reponse = $taf_auth->check_auth($reponse);
    if ($auth_reponse["status"] == false) {
        echo json_encode($auth_reponse);
        die;
    }

    $table_query = new TableQuery("users", $taf_config->get_db());
    $params = json_decode(file_get_contents("php://input"), true);

    if (empty($params)) {
        echo json_encode(["status" => false, "erreur" => "Paramètres manquants"]);
        die;
    }

    $conditionParams = json_decode($params["condition"] ?? "{}", true);
    $dataParams = json_decode($params["data"] ?? "{}", true);
    $currentmot_de_passe = $params["currentmot_de_passe"] ?? null;

    if (empty($conditionParams['id_users']) || empty($dataParams['mot_de_passe']) || empty($currentmot_de_passe)) {
        echo json_encode(["status" => false, "erreur" => "id_users, nouveau mot de passe ou mot de passe actuel manquant"]);
        die;
    }

    // Vérification du mot de passe actuel avec md5
    $stmt = $table_query->get_db()->prepare("SELECT mot_de_passe FROM users WHERE id_users = :id_users");
    $stmt->execute(['id_users' => $conditionParams['id_users']]);
    $users = $stmt->fetch();

    if (!$users || md5($currentmot_de_passe) !== $users['mot_de_passe']) {
        echo json_encode(["status" => false, "erreur" => "Mot de passe actuel incorrect"]);
        die;
    }

    // Pas de hashage pour le nouveau mot de passe, utiliser tel quel
    // Générer une requête UPDATE
    $condition = $table_query->dynamicCondition($conditionParams, '=');
    $query = $table_query->dynamicUpdate($dataParams, $condition);

    $stmt = $table_query->get_db()->prepare($query);

    // Bind des nouvelles données
    foreach ($dataParams as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    // Lier les conditions
    foreach ($conditionParams as $key => $value) {
        $stmt->bindValue(":cond_$key", $value);
    }

    if ($stmt->execute()) {
        echo json_encode(["status" => true, "message" => "Mot de passe mis à jour avec succès"]);
    } else {
        echo json_encode(["status" => false, "erreur" => "Erreur lors de la mise à jour"]);
    }
} catch (\Throwable $th) {
    echo json_encode(["status" => false, "erreur" => $th->getMessage()]);
}
