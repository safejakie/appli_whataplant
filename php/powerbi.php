<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $stmt = $pdo->query("
        SELECT 
            id_scan,
            id_utilisateur,
            nom_commun,
            nom_scientifique,
            famille,
            sante_plante,
            comestible,
            est_medicinale,
            est_toxique,
            niveau_toxicite,
            est_invasive,
            est_allelopathique,
            maladies_detectees,
            latitude,
            longitude,
            lieu,
            date_scan,
            YEAR(date_scan)                        AS annee,
            MONTH(date_scan)                       AS mois,
            DATE_FORMAT(date_scan, '%Y-%m')        AS mois_annee,
            CASE MONTH(date_scan)
                WHEN 1  THEN 'Janvier'
                WHEN 2  THEN 'Fevrier'
                WHEN 3  THEN 'Mars'
                WHEN 4  THEN 'Avril'
                WHEN 5  THEN 'Mai'
                WHEN 6  THEN 'Juin'
                WHEN 7  THEN 'Juillet'
                WHEN 8  THEN 'Aout'
                WHEN 9  THEN 'Septembre'
                WHEN 10 THEN 'Octobre'
                WHEN 11 THEN 'Novembre'
                WHEN 12 THEN 'Decembre'
            END                                    AS mois_nom,
            DATE(date_scan)                        AS date_seule
        FROM historique_scans
        ORDER BY date_scan DESC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        // Etat de sante normalise
        $sante = strtolower($row['sante_plante'] ?? '');
        if      (str_contains($sante, 'bonne'))    $row['etat_sante'] = 'Bonne';
        elseif  (str_contains($sante, 'moyenne'))  $row['etat_sante'] = 'Moyenne';
        elseif  (str_contains($sante, 'mauvaise')) $row['etat_sante'] = 'Mauvaise';
        elseif  (str_contains($sante, 'critique')) $row['etat_sante'] = 'Critique';
        else                                       $row['etat_sante'] = 'Non determine';

        // Booleens -> 0/1
        $row['est_medicinale']     = (int) $row['est_medicinale'];
        $row['est_toxique']        = (int) $row['est_toxique'];
        $row['est_invasive']       = (int) $row['est_invasive'];
        $row['est_allelopathique'] = (int) $row['est_allelopathique'];

        // Coordonnees en float
        $row['latitude']  = $row['latitude']  !== null ? (float)$row['latitude']  : null;
        $row['longitude'] = $row['longitude'] !== null ? (float)$row['longitude'] : null;

        $md = trim($row['maladies_detectees'] ?? '');
        $mdLower = strtolower($md);
        if (empty($md) || $mdLower === 'aucun' || $mdLower === 'aucune' || $mdLower === 'aucune maladie detectee' || $mdLower === 'aucune maladie détectée') {
            $row['maladies_detectees'] = 'Aucune';
        } else {
            $row['maladies_detectees'] = $md;
        }

        // Comestible lisible
        $c = strtolower(trim($row['comestible'] ?? ''));
        $row['comestible_label'] = ($c === 'oui') ? 'Comestible' : (($c === 'partiellement') ? 'Partiellement' : 'Non comestible');

        // Risque global
        if ($row['est_toxique'])        $row['risque'] = 'Toxique';
        elseif ($row['est_invasive'])   $row['risque'] = 'Invasive';
        elseif ($row['est_allelopathique']) $row['risque'] = 'Allelopathique';
        else                            $row['risque'] = 'Aucun risque';
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>