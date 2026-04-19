<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

try {
    // Vérifier propriétaire
    $check = $pdo->prepare("SELECT id_scan FROM historique_scans WHERE id_scan = ? AND id_utilisateur = ?");
    $check->execute([$id, $_SESSION['user_id']]);
    
    if ($check->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }
    
    // Supprimer
    $stmt = $pdo->prepare("DELETE FROM historique_scans WHERE id_scan = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD']);
}
?>