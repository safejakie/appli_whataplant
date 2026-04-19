<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id_scan,
            nom_commun,
            nom_scientifique,
            famille,
            description,
            sante_plante,
            maladies_detectees,
            comestible,
            parties_comestibles,
            idees_recettes,
            est_medicinale,
            maladies_soignees,
            posologie,
            contre_indications,
            est_toxique,
            niveau_toxicite,
            est_invasive,
            est_allelopathique,
            image_base64,
            date_scan,
            lieu
        FROM historique_scans
        WHERE id_scan = ? AND id_utilisateur = ?
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $scan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($scan) {
        // Convertir les booléens
        $scan['est_medicinale']    = (bool)$scan['est_medicinale'];
        $scan['est_toxique']       = (bool)$scan['est_toxique'];
        $scan['est_invasive']      = (bool)$scan['est_invasive'];
        $scan['est_allelopathique'] = (bool)$scan['est_allelopathique'];

        echo json_encode(['success' => true] + $scan);
    } else {
        echo json_encode(['success' => false, 'message' => 'Scan non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur get_scan_details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
}
?>