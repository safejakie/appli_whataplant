<?php
// delete_conversation.php - Supprime une conversation
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$convId = $data['conversation_id'] ?? null;

if (!$convId) {
    echo json_encode(['success' => false, 'message' => 'ID conversation manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM chat_history WHERE conversation_id = ? AND id_utilisateur = ?");
    $stmt->execute([$convId, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Erreur delete_conversation: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>