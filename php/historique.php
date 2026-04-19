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
                
                <!-- 🔧 AJOUT: Boutons d'action (Voir + Supprimer) -->
                <div class="scan-card-actions">
                    <button class="btn-voir-details" onclick="event.stopPropagation(); showScanDetails(<?= $row['id_scan'] ?>)">
                        <i class="fa-solid fa-eye"></i> Voir détails
                    </button>
                    <button class="btn-supprimer" onclick="event.stopPropagation(); deleteScan(<?= $row['id_scan'] ?>, this)" title="Supprimer ce scan">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
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

/* 🔧 AJOUT : Styles des boutons d'action */
.scan-card-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.btn-voir-details {
    flex: 1;
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

/* 🔧 AJOUT : Bouton supprimer */
.btn-supprimer {
    width: 40px;
    height: 40px;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-supprimer:hover {
    background: #dc2626;
    color: white;
    transform: scale(1.05);
}

.scan-card.deleting {
    opacity: 0.5;
    transform: scale(0.95);
    pointer-events: none;
}
</style>

<script>

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

// 🔧 AJOUT : Fonction de suppression
async function deleteScan(id, btnElement) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce scan ?')) {
        return;
    }
    
    const card = btnElement.closest('.scan-card');
    card.classList.add('deleting');
    
    try {
        const response = await fetch('php/delete_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Animation de suppression
            card.style.transform = 'scale(0.8)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.remove();
                
                // Vérifier s'il reste des cartes
                const remaining = document.querySelectorAll('.scan-card');
                if (remaining.length === 0) {
                    document.getElementById('scans-grid').innerHTML = 
                        '<div class="empty-state">Aucun scan pour le moment</div>';
                }
            }, 300);
            
        } else {
            alert(data.message || 'Erreur lors de la suppression');
            card.classList.remove('deleting');
        }
    } catch (e) {
        alert('Erreur réseau');
        card.classList.remove('deleting');
        console.error('Erreur delete:', e);
    }
}
</script>