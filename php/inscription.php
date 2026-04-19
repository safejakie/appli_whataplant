<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

$userId = $_SESSION['user_id'];

$scans = $pdo->prepare("
    SELECT id_scan, nom_commun, nom_scientifique, image_base64, date_scan 
    FROM historique_scans 
    WHERE id_utilisateur = ? 
    ORDER BY date_scan DESC
");
$scans->execute([$userId]);
?>

<div class="view-header-simple">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Historique des scans</h2>
    <p>Toutes vos plantes scannées</p>
</div>

<div class="search-filter">
    <input type="text" class="search-input" placeholder="Rechercher une plante..." id="search-history">
</div>

<div class="scans-grid" id="scans-grid">
    <?php while ($row = $scans->fetch()): ?>
        <div class="scan-card" data-id="<?= $row['id_scan'] ?>">
            <div class="scan-card-img">
                <?php if ($row['image_base64']): ?>
                    <img src="<?= htmlspecialchars($row['image_base64']) ?>" alt="<?= htmlspecialchars($row['nom_commun']) ?>">
                <?php else: ?>
                    <div class="no-image"><i class="fa-solid fa-leaf"></i></div>
                <?php endif; ?>
            </div>
            <div class="scan-card-body">
                <div class="scan-card-name"><?= htmlspecialchars($row['nom_commun']) ?></div>
                <div class="scan-card-sci"><?= htmlspecialchars($row['nom_scientifique']) ?></div>
                <div class="scan-card-date"><?= date('d/m/Y H:i', strtotime($row['date_scan'])) ?></div>
                <!-- 🔧 AJOUT : Bouton voir détails -->
                <button class="btn-voir-details" onclick="event.stopPropagation(); showScanDetails(<?= $row['id_scan'] ?>)">
                    <i class="fa-solid fa-eye"></i> Voir détails
                </button>
            </div>
        </div>
    <?php endwhile; ?>
    <?php if ($scans->rowCount() == 0): ?>
        <div class="empty-state">Aucun scan pour le moment</div>
    <?php endif; ?>
</div>

<!-- MODAL SUPERPOSÉ -->
<div id="scanModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fa-solid fa-leaf"></i> Détails de la plante</h3>
            <button class="modal-close-btn" onclick="closeScanModal()">&times;</button>
        </div>
        <div class="modal-body" id="scanModalBody">
            <div class="loading-spinner"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement...</div>
        </div>
    </div>
</div>

<style>
/* Modal overlay avec flou */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}
.modal-container {
    background: white;
    border-radius: 28px;
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    animation: modalFadeIn 0.3s ease;
}
@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    background: linear-gradient(135deg, var(--g1), var(--g2));
    color: white;
    border-radius: 28px 28px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
}
.modal-header h3 {
    font-size: 20px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.modal-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    line-height: 1;
    transition: transform 0.2s;
}
.modal-close-btn:hover {
    transform: scale(1.1);
}
.modal-body {
    padding: 24px;
}
/* Cartes d'informations */
.info-card {
    background: #f8faf8;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 16px;
    border-left: 4px solid var(--g1);
}
.info-card-title {
    font-weight: 700;
    color: var(--g1);
    font-size: 14px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.info-card-content {
    font-size: 14px;
    color: var(--t1);
    line-height: 1.5;
}
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    margin: 4px 4px 4px 0;
}
.badge-oui {
    background: #dcfce7;
    color: #15803d;
}
.badge-non {
    background: #fee2e2;
    color: #dc2626;
}
.badge-toxique {
    background: #fef3c7;
    color: #d97706;
}
.badge-medicinale {
    background: #e0f2fe;
    color: #0369a1;
}
.badge-invasive {
    background: #fef3c7;
    color: #d97706;
}
.explanation {
    font-size: 12px;
    color: var(--t3);
    margin-top: 8px;
    padding-left: 16px;
    border-left: 2px solid var(--b2);
}
.plant-image-modal {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 16px;
    margin-bottom: 16px;
}
.loading-spinner {
    text-align: center;
    padding: 40px;
    color: var(--t3);
}
.empty-state {
    text-align: center;
    padding: 60px;
    color: var(--t3);
}
.no-image {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: var(--t4);
}
.scan-card {
    cursor: pointer;
}

/* 🔧 AJOUT : Style du bouton voir détails */
.btn-voir-details {
    margin-top: 12px;
    width: 100%;
    padding: 10px 16px;
    background: linear-gradient(135deg, var(--g1), var(--g2));
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-voir-details:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(45, 122, 58, 0.3);
}

.btn-voir-details i {
    font-size: 14px;
}
</style>

<script>
async function showScanDetails(id) {
    const modal = document.getElementById('scanModal');
    const body = document.getElementById('scanModalBody');
    
    modal.style.display = 'flex';
    body.innerHTML = '<div class="loading-spinner"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement des détails...</div>';
    
    try {
        const res = await fetch('get_scan_details.php?id=' + id);
        const data = await res.json();
        
        if (data.success) {
            let comestibleBadge = '';
            if (data.comestible === 'Oui') {
                comestibleBadge = '<span class="badge badge-oui"><i class="fa-solid fa-check"></i> Comestible</span>';
            } else if (data.comestible === 'Partiellement') {
                comestibleBadge = '<span class="badge" style="background:#fef3c7;color:#d97706"><i class="fa-solid fa-circle-half-stroke"></i> Partiellement comestible</span>';
            } else {
                comestibleBadge = '<span class="badge badge-non"><i class="fa-solid fa-xmark"></i> Non comestible</span>';
            }
            
            let toxiqueBadge = '';
            if (data.est_toxique) {
                toxiqueBadge = `<span class="badge badge-toxique"><i class="fa-solid fa-skull-crossbones"></i> Toxique (${data.niveau_toxicite || 'Niveau inconnu'})</span>`;
            } else {
                toxiqueBadge = '<span class="badge badge-non"><i class="fa-solid fa-check"></i> Non toxique</span>';
            }
            
            let medicinalBadge = '';
            if (data.est_medicinale) {
                medicinalBadge = '<span class="badge badge-medicinale"><i class="fa-solid fa-flask"></i> Plante médicinale</span>';
            } else {
                medicinalBadge = '<span class="badge badge-non"><i class="fa-solid fa-xmark"></i> Non médicinale</span>';
            }
            
            let invasiveBadge = '';
            let invasiveExplanation = '';
            if (data.est_invasive) {
                invasiveBadge = '<span class="badge badge-invasive"><i class="fa-solid fa-virus"></i> Plante invasive</span>';
                invasiveExplanation = '<div class="explanation"><strong>⚠️ Pourquoi invasive ?</strong> Cette plante se reproduit rapidement, peut coloniser de nouveaux territoires et concurrencer les espèces locales, causant un déséquilibre écologique.</div>';
            } else {
                invasiveBadge = '<span class="badge badge-non"><i class="fa-solid fa-check"></i> Non invasive</span>';
                invasiveExplanation = '<div class="explanation">Cette plante ne présente pas de risque invasif pour l\'écosystème local.</div>';
            }
            
            let alleloBadge = '';
            let alleloExplanation = '';
            if (data.est_allelopathique) {
                alleloBadge = '<span class="badge badge-invasive"><i class="fa-solid fa-flask"></i> Plante allélopathique</span>';
                alleloExplanation = '<div class="explanation"><strong>⚠️ Pourquoi allélopathique ?</strong> Cette plante libère des substances chimiques qui inhibent la germination ou la croissance des plantes voisines, modifiant ainsi l\'écosystème environnant.</div>';
            } else {
                alleloBadge = '<span class="badge badge-non"><i class="fa-solid fa-check"></i> Non allélopathique</span>';
                alleloExplanation = '<div class="explanation">Cette plante n\'affecte pas négativement la croissance des plantes voisines.</div>';
            }
            
            body.innerHTML = `
                ${data.image_base64 ? `<img src="${data.image_base64}" class="plant-image-modal" alt="${escapeHtml(data.nom_commun)}">` : ''}
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-tag"></i> Nom commun</div>
                    <div class="info-card-content"><strong>${escapeHtml(data.nom_commun)}</strong></div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-microscope"></i> Nom scientifique</div>
                    <div class="info-card-content"><em>${escapeHtml(data.nom_scientifique)}</em></div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-tree"></i> Famille</div>
                    <div class="info-card-content">${escapeHtml(data.famille)}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-book-open"></i> Description</div>
                    <div class="info-card-content">${escapeHtml(data.description) || 'Aucune description disponible'}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-heartbeat"></i> Santé de la plante</div>
                    <div class="info-card-content">${escapeHtml(data.sante_plante) || 'Aucune information sur la santé'}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-utensils"></i> Comestibilité</div>
                    <div class="info-card-content">
                        ${comestibleBadge}
                        ${data.parties_comestibles ? `<div style="margin-top:8px"><strong>Parties comestibles :</strong> ${escapeHtml(data.parties_comestibles)}</div>` : ''}
                        ${data.idees_recettes ? `<div style="margin-top:8px"><strong>Idées recettes :</strong> ${escapeHtml(data.idees_recettes)}</div>` : ''}
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-flask-vial"></i> Propriétés médicinales</div>
                    <div class="info-card-content">
                        ${medicinalBadge}
                        ${data.maladies_soignees ? `<div style="margin-top:8px"><strong>Maladies soignées :</strong> ${escapeHtml(data.maladies_soignees)}</div>` : ''}
                        ${data.posologie ? `<div style="margin-top:8px"><strong>Posologie :</strong> ${escapeHtml(data.posologie)}</div>` : ''}
                        ${data.contre_indications ? `<div style="margin-top:8px"><strong>⚠️ Contre-indications :</strong> ${escapeHtml(data.contre_indications)}</div>` : ''}
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-skull-crossbones"></i> Toxicité</div>
                    <div class="info-card-content">${toxiqueBadge}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-earth-africa"></i> Environnement</div>
                    <div class="info-card-content">
                        ${invasiveBadge}
                        ${invasiveExplanation}
                        <div style="margin-top:12px"></div>
                        ${alleloBadge}
                        ${alleloExplanation}
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-regular fa-calendar"></i> Date du scan</div>
                    <div class="info-card-content">${new Date(data.date_scan).toLocaleString('fr-FR')}</div>
                </div>
            `;
        } else {
            body.innerHTML = `<div class="info-card"><div class="info-card-content" style="color:var(--danger)">❌ Erreur: ${escapeHtml(data.message)}</div></div>`;
        }
    } catch(err) {
        console.error('Erreur:', err);
        body.innerHTML = `<div class="info-card"><div class="info-card-content" style="color:var(--danger)">❌ Erreur de connexion au serveur</div></div>`;
    }
}

function closeScanModal() {
    document.getElementById('scanModal').style.display = 'none';
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

document.getElementById('scanModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScanModal();
    }
});

const searchInput = document.getElementById('search-history');
if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.scan-card').forEach(card => {
            const name = card.querySelector('.scan-card-name')?.innerText.toLowerCase() || '';
            card.style.display = name.includes(term) ? '' : 'none';
        });
    });
}
</script>