<?php
// get_chat.php - Récupère toutes les conversations
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

try {
    // Récupère toutes les conversations avec leur dernier message et date
    $stmt = $pdo->prepare("
        SELECT 
            conversation_id, 
            MAX(date_chat) as last_date,
            (SELECT message FROM chat_history ch2 
             WHERE ch2.conversation_id = ch1.conversation_id 
             ORDER BY date_chat DESC LIMIT 1) as last_message
        FROM chat_history ch1
        WHERE id_utilisateur = ?
        GROUP BY conversation_id
        ORDER BY last_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($conversations);
} catch (PDOException $e) {
    error_log("Erreur get_chat: " . $e->getMessage());
    echo json_encode([]);
}
?>