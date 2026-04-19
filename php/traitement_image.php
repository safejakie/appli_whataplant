<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['nom_commun'])) {
    http_response_code(400);
    echo json_encode(["error" => "Données invalides"]);
    exit;
}

// ========== SANTÉ ==========
$santeTexte = '';
if (isset($data['sante']) && is_array($data['sante'])) {
    $etat = $data['sante']['etat'] ?? 'Inconnue';
    $causes = $data['sante']['causes'] ?? '';
    $recommandations = $data['sante']['recommandations'] ?? '';
    
    $parts = ["État : $etat"];
    if (!empty($causes)) $parts[] = "Causes probables : $causes";
    if (!empty($recommandations)) $parts[] = "Recommandations : $recommandations";
    
    $santeTexte = implode("\n", $parts);
} elseif (isset($data['sante_plante'])) {
    $santeTexte = $data['sante_plante'];
}

// ========== MALADIES DÉTECTÉES ==========
$maladiesDetectees = '';

if (isset($data['sante']['maladies_detectees'])) {
    $md = $data['sante']['maladies_detectees'];
    if (is_array($md) && count($md) > 0) {
        $maladiesDetectees = implode(', ', $md);
    } elseif (is_string($md) && trim($md) !== '') {
        $maladiesDetectees = $md;
    }
} elseif (isset($data['maladies_detectees'])) {
    $md = $data['maladies_detectees'];
    if (is_array($md) && count($md) > 0) {
        $maladiesDetectees = implode(', ', $md);
    } elseif (is_string($md) && trim($md) !== '') {
        $maladiesDetectees = $md;
    }
}

// Si aucune maladie, afficher "Aucune" en base (Power BI filtre cette valeur côté rapport)
if (empty($maladiesDetectees)) {
    $maladiesDetectees = 'Aucune';
}

// ========== GÉOLOCALISATION ==========
$latitude = null;
$longitude = null;
$lieu = '';

if (isset($data['latitude']) && is_numeric($data['latitude'])) {
    $latitude = (float) $data['latitude'];
}
if (isset($data['longitude']) && is_numeric($data['longitude'])) {
    $longitude = (float) $data['longitude'];
}
if (isset($data['lieu']) && !empty($data['lieu'])) {
    $lieu = $data['lieu'];
}

// ========== MALADIES SOIGNÉES ==========
$maladiesSoignees = '';
if (isset($data['maladies_traitees'])) {
    $source = $data['maladies_traitees'];
    if (is_array($source)) {
        $maladiesSoignees = implode(', ', $source);
    } elseif (is_string($source)) {
        $maladiesSoignees = $source;
    }
}

// Booléens
$estMedicinale = !empty($data['est_medicinale']) ? 1 : 0;
$estToxique = !empty($data['est_toxique']) ? 1 : 0;
$estInvasive = !empty($data['est_invasive']) ? 1 : 0;
$estAllelopathique = !empty($data['est_allelopathique']) ? 1 : 0;

try {
    $insert = $pdo->prepare("
        INSERT INTO historique_scans (
            id_utilisateur, nom_commun, nom_scientifique, famille, description, 
            sante_plante, maladies_detectees,
            comestible, parties_comestibles, idees_recettes,
            est_medicinale, maladies_soignees, posologie, contre_indications,
            est_toxique, niveau_toxicite, est_invasive, est_allelopathique, 
            image_base64, latitude, longitude, lieu, date_scan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $insert->execute([
        $_SESSION['user_id'],
        $data['nom_commun'] ?? 'Inconnu',
        $data['nom_scientifique'] ?? '',
        $data['famille'] ?? '',
        $data['description'] ?? '',
        $santeTexte,
        $maladiesDetectees,      // ← Toujours rempli
        $data['comestible'] ?? 'Non',
        $data['parties_comestibles'] ?? '',
        $data['idees_recettes'] ?? '',
        $estMedicinale,
        $maladiesSoignees,
        $data['posologie'] ?? '',
        $data['contre_indications'] ?? '',
        $estToxique,
        $data['niveau_toxicite'] ?? 'Aucun',
        $estInvasive,
        $estAllelopathique,
        $data['image_b64'] ?? '',
        $latitude,               // ← Coordonnées
        $longitude,              // ← Coordonnées
        $lieu                    // ← Ville, Pays
    ]);

    echo json_encode([
        "success" => true, 
        "message" => "Scan sauvegardé"
    ]);

} catch (PDOException $e) {
    error_log("Erreur insert: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>