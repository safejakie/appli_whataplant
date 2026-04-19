<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';   // ← Important : on utilise la config centrale

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Adaptation aux noms de champs que tu utilises dans le formulaire
$nom_complet = trim($data['nom_complet'] ?? $data['nom'] ?? '');
$email       = trim($data['email'] ?? '');

if (!$nom_complet || !$email) {
    echo json_encode(['success' => false, 'message' => 'Nom et email sont obligatoires']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

try {
    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    $check = $pdo->prepare("
        SELECT id_utilisateur 
        FROM utilisateurs 
        WHERE email = ? AND id_utilisateur != ?
    ");
    $check->execute([$email, $_SESSION['user_id']]);
    
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre compte']);
        exit;
    }
    
    // Mise à jour du profil
    $stmt = $pdo->prepare("
        UPDATE utilisateurs 
        SET nom_complet = ?, 
            email = ? 
        WHERE id_utilisateur = ?
    ");
    
    $stmt->execute([$nom_complet, $email, $_SESSION['user_id']]);

    // Mise à jour de la session
    $_SESSION['user_nom'] = $nom_complet;   // pour compatibilité si utilisé ailleurs

    echo json_encode([
        'success' => true, 
        'message' => 'Profil mis à jour avec succès'
    ]);

} catch (PDOException $e) {
    error_log("Erreur update_profil: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
    ]);
}
?>