<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');
$reponse = trim($data['reponse'] ?? '');
$convId = isset($data['conversation_id']) && $data['conversation_id'] ? $data['conversation_id'] : null;

if (!$message || !$reponse) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    if (!$convId) {
        $convId = 'conv_' . time() . '_' . $_SESSION['user_id'];
    }
    
    // Vérifier si la table existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id VARCHAR(100) NOT NULL,
            id_utilisateur INT NOT NULL,
            message TEXT NOT NULL,
            reponse TEXT NOT NULL,
            date_chat DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conversation (conversation_id),
            INDEX idx_user (id_utilisateur)
        )
    ");
    
    $stmt = $pdo->prepare("
        INSERT INTO chat_history (conversation_id, id_utilisateur, message, reponse, date_chat)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$convId, $_SESSION['user_id'], $message, $reponse]);
    
    echo json_encode(['success' => true, 'conversation_id' => $convId]);
} catch (PDOException $e) {
    error_log("Erreur save_chat: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>