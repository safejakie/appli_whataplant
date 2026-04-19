<?php
// php/update_mdp.php
// Appelé par nv_mdp.html après que Firebase a validé et consommé le lien
header('Content-Type: application/json');
require_once 'config.php';

$data     = json_decode(file_get_contents('php://input'), true);
$email    = $data['email']    ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Mot de passe trop court']);
    exit;
}

try {
    // PASSWORD_BCRYPT — cohérent avec inscription.php et connexion.php
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Mise à jour dans la table "utilisateurs", colonne "mot_de_passe"
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun utilisateur trouvé avec cet email']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}
?>