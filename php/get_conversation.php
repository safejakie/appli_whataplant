<?php
// get_conversation.php - Récupère une conversation complète
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$convId = $_GET['id'] ?? null;
if (!$convId) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT message, reponse, date_chat
        FROM chat_history
        WHERE conversation_id = ? AND id_utilisateur = ?
        ORDER BY date_chat ASC
    ");
    $stmt->execute([$convId, $_SESSION['user_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fil = [];
    foreach ($rows as $row) {
        $fil[] = ['role' => 'user', 'content' => $row['message']];
        $fil[] = ['role' => 'assistant', 'content' => $row['reponse']];
    }

    echo json_encode($fil);
} catch (PDOException $e) {
    error_log("Erreur get_conversation: " . $e->getMessage());
    echo json_encode([]);
}
?>