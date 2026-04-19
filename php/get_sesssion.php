<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged' => false]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT nom_complet FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nomComplet = $user['nom_complet'] ?? ($_SESSION['user_nom'] ?? 'Utilisateur');

    $stats = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            COUNT(DISTINCT nom_commun) as uniques,
            SUM(CASE WHEN est_medicinale = 1 THEN 1 ELSE 0 END) as med,
            SUM(CASE WHEN comestible = 'Oui' THEN 1 ELSE 0 END) as com
        FROM historique_scans 
        WHERE id_utilisateur = ?
    ");
    $stats->execute([$userId]);
    $s = $stats->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'logged' => true,
        'nom' => $nomComplet,
        'user_id' => $userId,
        'stats' => [
            'total' => (int)($s['total'] ?? 0),
            'uniques' => (int)($s['uniques'] ?? 0),
            'med' => (int)($s['med'] ?? 0),
            'com' => (int)($s['com'] ?? 0)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'logged' => true,
        'nom' => $_SESSION['user_nom'] ?? 'Utilisateur',
        'user_id' => $userId,
        'stats' => ['total' => 0, 'uniques' => 0, 'med' => 0, 'com' => 0],
        'error' => $e->getMessage()
    ]);
}
?>