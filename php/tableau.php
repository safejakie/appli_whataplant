<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit;
}

$userId = $_SESSION['user_id'];
$userStmt = $pdo->prepare("SELECT nom_complet FROM utilisateurs WHERE id_utilisateur = ?");
$userStmt->execute([$userId]);
$userName = $userStmt->fetchColumn() ?: 'Utilisateur';

$initiales = implode('', array_map(fn($w) => mb_substr($w, 0, 1, 'UTF-8'), array_slice(explode(' ', $userName), 0, 2)));
$prenom = explode(' ', $userName)[0];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BotanIA – <?= htmlspecialchars($prenom) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,300;0,500;0,700;1,300&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style/tableau.css">
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-seedling"></i></div>
        <div class="logo-text">Botan<span>IA</span></div>
    </div>
    <button class="new-chat-btn" onclick="newConversation()">
        <i class="fa-solid fa-plus"></i> Nouvelle conversation
    </button>
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Menu</div>
        <div class="nav-item active" data-view="accueil" onclick="switchView('accueil')">
            <i class="fa-solid fa-house"></i><span>Accueil</span>
        </div>
        <div class="nav-item" data-view="historique" onclick="switchView('historique')">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Historique des scans</span>
        </div>
        <div class="nav-item" data-view="profil" onclick="switchView('profil')">
            <i class="fa-solid fa-user-circle"></i><span>Mon profil</span>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip" onclick="switchView('profil')">
            <div class="avatar"><?= htmlspecialchars(strtoupper($initiales)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-status"><span class="status-dot"></span> En ligne</div>
            </div>
        </div>
        <a href="deconnexion.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<main class="main">
    <button class="burger-menu-btn" id="burgerMenuBtn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </button>
    
    <button class="conv-floating-btn" id="convFloatingBtn" style="display: flex;" onclick="toggleConvDrawer()">
        <i class="fa-regular fa-comments"></i>
    </button>

    <div class="conv-drawer" id="convDrawer">
        <div class="drawer-head">
            <div class="drawer-head-title"><i class="fa-regular fa-comments"></i> Conversations</div>
            <button class="drawer-close" onclick="toggleConvDrawer()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="conv-list" id="conv-list">
            <div class="conv-empty"><i class="fa-regular fa-comment-dots"></i><p>Aucune conversation</p><small>Posez votre première question !</small></div>
        </div>
    </div>
    <div class="drawer-overlay" id="drawerOverlay" onclick="toggleConvDrawer()"></div>

    <!-- VUE ACCUEIL -->
    <div id="view-accueil" class="view view-accueil active">
        <div class="messages-area" id="chat-area">
            <div class="welcome-screen">
                <div class="welcome-leaf">🌿</div>
                <div class="welcome-text">
                    <p class="welcome-sub">Bienvenue,</p>
                    <h1 class="welcome-name"><?= htmlspecialchars($prenom) ?> !</h1>
                </div>
                <p class="welcome-hint">Identifiez une plante en quelques secondes</p>
                
                <div class="action-cards">
                    <div class="action-card" onclick="document.getElementById('file-input').click()">
                        <div class="card-icon"><i class="fa-solid fa-cloud-upload-alt"></i></div>
                        <div class="card-title">Importer une photo</div>
                        <div class="card-desc">Chargez une image</div>
                        <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                    <div class="action-card" onclick="startCamera()">
                        <div class="card-icon"><i class="fa-solid fa-camera"></i></div>
                        <div class="card-title">Scanner</div>
                        <div class="card-desc">Prenez une photo</div>
                        <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-input-area" id="chatInputArea" style="display: none;">

            <div class="chat-input-row" id="chatInputRow">
                <button class="input-action-btn" onclick="document.getElementById('file-input').click()" title="Importer une photo">
                    <i class="fa-solid fa-image"></i>
                </button>
                <button class="input-action-btn" onclick="startCamera()" title="Scanner avec la caméra">
                    <i class="fa-solid fa-camera"></i>
                </button>
                <textarea
                    id="message-input"
                    class="chat-textarea"
                    rows="1"
                    placeholder="Posez une question sur la plante identifiée…"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg();}"
                    oninput="autoResize(this)"
                ></textarea>
                <button class="send-btn" id="send-btn" onclick="sendMsg()">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- VUE HISTORIQUE -->
    <div id="view-historique" class="view" style="display:none">
        <div class="content-area" id="historique-content">
            <div class="loading-state"><div class="spin"></div><span>Chargement…</span></div>
        </div>
    </div>
    
    <!-- VUE STATISTIQUES -->
    <div id="view-statistiques" class="view" style="display:none">
        <div class="content-area" id="statistiques-content">
            <div class="loading-state"><div class="spin"></div><span>Chargement…</span></div>
        </div>
    </div>
    
    <!-- VUE PROFIL -->
    <div id="view-profil" class="view" style="display:none">
        <div class="content-area" id="profil-content">
            <div class="loading-state"><div class="spin"></div><span>Chargement…</span></div>
        </div>
    </div>
</main>

<!-- MODAL CAMÉRA -->
<div id="camera-modal">
    <div class="camera-box">
        <div class="camera-head">
            <span><i class="fa-solid fa-camera"></i> Scanner une plante</span>
            <button onclick="stopCamera()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="camera-body">
            <video id="video" autoplay playsinline></video>
            <div class="cam-frame">
                <span class="cf tl"></span>
                <span class="cf tr"></span>
                <span class="cf bl"></span>
                <span class="cf br"></span>
            </div>
            <div class="cam-hint">Centrez la plante dans le cadre</div>
        </div>
        <div class="camera-foot">
            <button class="btn-capture" onclick="capturePhoto()"><i class="fa-solid fa-circle"></i> Capturer</button>
            <button class="btn-cancel-cam" onclick="stopCamera()">Annuler</button>
        </div>
    </div>
</div>

<!-- 🔧 AJOUT : MODAL DÉTAILS SCAN (déplacé depuis historique.php) -->
<div id="scanModal" class="scan-modal-overlay">
    <div class="scan-modal-container">
        <div class="scan-modal-header">
            <h3><i class="fa-solid fa-leaf"></i> Détails de la plante</h3>
            <button class="scan-modal-close-btn" onclick="closeScanModal()">&times;</button>
        </div>
        <div class="scan-modal-body" id="scanModalBody">
            <div class="loading-spinner"><i class="fa-solid fa-circle-notch fa-spin"></i> Chargement...</div>
        </div>
    </div>
</div>

<!-- AGENT IA FLOTTANT -->
<div class="agent-floating" id="agentFloating" onclick="toggleChatInput()">
    <div class="agent-circle">
        <i class="fa-solid fa-seedling"></i>
        <span class="agent-dot"></span>
    </div>
    <div class="agent-tooltip">Cliquez pour poser une question 🌿</div>
</div>

<input type="file" id="file-input" style="display:none" accept="image/*" onchange="uploadImage(event)">
<div id="toasts"></div>

<script>
const API_BASE = '<?= rtrim(getenv("PYTHON_API_URL") ?: "http://localhost:5001", "/") ?>';
const USER_NAME = <?= json_encode($userName) ?>;
const USER_INIT = <?= json_encode(strtoupper($initiales)) ?>;
const USER_ID = <?= json_encode($userId) ?>;

let loadedViews = { accueil: true, historique: false, statistiques: false, profil: false };
let allConvs = [];
let currentConvId = null;
let currentPlantContext = null;

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
}

function toggleChatInput() {
    const inputArea = document.getElementById('chatInputArea');
    const agentFloating = document.getElementById('agentFloating');
    
    if (inputArea.style.display === 'none' || inputArea.style.display === '') {
        inputArea.style.display = 'block';
        agentFloating.classList.add('moved-up');
        setTimeout(() => {
            document.getElementById('message-input').focus();
            inputArea.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 100);
    } else {
        inputArea.style.display = 'none';
        agentFloating.classList.remove('moved-up');
    }
}

function newConversation() { 
    currentConvId = null; 
    currentPlantContext = null; 
    clearChat(); 
    showWelcome(); 
    document.getElementById('chatInputArea').style.display = 'none';
    document.getElementById('agentFloating').classList.remove('moved-up');
    document.getElementById('sug-chips').style.display = 'flex'; 
    document.getElementById('message-input').value = ''; 
    switchView('accueil'); 
    loadConversations(); 
}

function switchView(view) {
    document.querySelectorAll('.view').forEach(v => v.style.display = 'none');
    const targetView = document.getElementById('view-' + view);
    if (targetView) targetView.style.display = 'flex';
    
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const nav = document.querySelector(`.nav-item[data-view="${view}"]`);
    if (nav) nav.classList.add('active');
    
    const convBtn = document.getElementById('convFloatingBtn');
    if (view === 'accueil') {
        convBtn.style.display = 'flex';
    } else {
        convBtn.style.display = 'none';
    }
    
    const agent = document.getElementById('agentFloating');
    const chatInputArea = document.getElementById('chatInputArea');
    
    if (view === 'accueil') {
        if (chatInputArea.style.display === 'none' || chatInputArea.style.display === '') {
            agent.style.display = 'flex';
        }
    } else {
        agent.style.display = 'none';
    }
    
    if (view !== 'accueil') {
        document.getElementById('chatInputArea').style.display = 'none';
        document.getElementById('agentFloating').classList.remove('moved-up');
    }
    
    if (!loadedViews[view]) {
        loadViewContent(view);
    }
}

function toggleConvDrawer() { 
    const d = document.getElementById('convDrawer'); 
    const o = document.getElementById('drawerOverlay'); 
    d.classList.toggle('open'); 
    o.classList.toggle('active'); 
    if (d.classList.contains('open')) loadConversations(); 
}

async function loadViewContent(view) {
    const el = document.getElementById(view + '-content');
    if (!el) return;
    try { 
        const r = await fetch(view + '.php?_t=' + Date.now());
        if (r.ok) {
            el.innerHTML = await r.text(); 
            loadedViews[view] = true;
            if (view === 'historique') attachHistoryEvents();
            else if (view === 'statistiques') attachStatsEvents();
            else if (view === 'profil') attachProfilEvents();
        } else {
            el.innerHTML = `<div class="error-state"><i class="fa-solid fa-circle-exclamation"></i><p>Erreur ${r.status}</p></div>`;
        }
    } catch(e) { 
        console.error('loadViewContent error:', e);
        el.innerHTML = `<div class="error-state"><i class="fa-solid fa-circle-exclamation"></i><p>Erreur de chargement</p></div>`; 
    }
}

function attachHistoryEvents() {
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
}

function attachStatsEvents() {}
function attachProfilEvents() {
    const form = document.getElementById('editProfileForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('update_profil.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });

                const result = await res.json();

                if (result.success) {
                    toast('Profil mis à jour avec succès !', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toast(result.message || 'Erreur inconnue', 'error');
                    console.error('Erreur serveur:', result);
                }
            } catch(err) {
                console.error('Erreur complète:', err);
                toast('Impossible de contacter le serveur. Vérifiez la console (F12)', 'error');
            }
        });
    }
}

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
                toxiqueBadge = `<span class="badge badge-toxique"><i class="fa-solid fa-skull-crossbones"></i> Toxique (${esc(data.niveau_toxicite || 'Niveau inconnu')})</span>`;
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
            
            // 🔧 MALADIES DÉTECTÉES
            let maladiesDetecteesHTML = '';
            const mdVal = data.maladies_detectees ? data.maladies_detectees.trim() : '';
            const mdLower = mdVal.toLowerCase();
            const estVide = mdVal === '' || mdLower === 'aucune' || mdLower === 'aucune maladie détectée';
            if (!estVide) {
                maladiesDetecteesHTML = `
                    <div class="info-card">
                        <div class="info-card-title"><i class="fa-solid fa-virus"></i> Maladies détectées</div>
                        <div class="info-card-content">${esc(mdVal)}</div>
                    </div>
                `;
            } else {
                maladiesDetecteesHTML = `
                    <div class="info-card">
                        <div class="info-card-title"><i class="fa-solid fa-virus"></i> Maladies détectées</div>
                        <div class="info-card-content" style="color:#15803d"><i class="fa-solid fa-check"></i> Aucune maladie détectée</div>
                    </div>
                `;
            }
            
            // LIEU - Utilise directement le lieu stocké en base (Ville, Pays)
            let locationHTML = '';
            if (data.lieu && data.lieu.trim() !== '' && data.lieu !== 'Lieu inconnu') {
                locationHTML = `
                    <div class="info-card">
                        <div class="info-card-title"><i class="fa-solid fa-location-dot"></i> Lieu du scan</div>
                        <div class="info-card-content">
                            <i class="fa-solid fa-map-marker-alt"></i> ${esc(data.lieu)}
                        </div>
                    </div>
                `;
            } else if (data.latitude && data.longitude) {
                locationHTML = `
                    <div class="info-card">
                        <div class="info-card-title"><i class="fa-solid fa-location-dot"></i> Lieu du scan</div>
                        <div class="info-card-content">
                            <i class="fa-solid fa-map-marker-alt"></i> Position GPS enregistrée
                            <br><small>Lat: ${data.latitude.toFixed(4)}, Lng: ${data.longitude.toFixed(4)}</small>
                        </div>
                    </div>
                `;
            }
            
            body.innerHTML = `
                ${data.image_base64 ? `<img src="${data.image_base64}" class="plant-image-modal" alt="${esc(data.nom_commun)}">` : ''}
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-tag"></i> Nom commun</div>
                    <div class="info-card-content"><strong>${esc(data.nom_commun)}</strong></div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-microscope"></i> Nom scientifique</div>
                    <div class="info-card-content"><em>${esc(data.nom_scientifique)}</em></div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-tree"></i> Famille</div>
                    <div class="info-card-content">${esc(data.famille)}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-book-open"></i> Description</div>
                    <div class="info-card-content">${esc(data.description) || 'Aucune description disponible'}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-heartbeat"></i> Santé de la plante</div>
                    <div class="info-card-content">${data.sante_plante ? esc(data.sante_plante).replace(/\n/g, '<br>') : 'Aucune information sur la santé'}</div>
                </div>
                
                ${maladiesDetecteesHTML}
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-utensils"></i> Comestibilité</div>
                    <div class="info-card-content">
                        ${comestibleBadge}
                        ${data.parties_comestibles ? `<div style="margin-top:8px"><strong>Parties comestibles :</strong> ${esc(data.parties_comestibles)}</div>` : ''}
                        ${data.idees_recettes ? `<div style="margin-top:8px"><strong>Idées recettes :</strong> ${esc(data.idees_recettes)}</div>` : ''}
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-solid fa-flask-vial"></i> Propriétés médicinales</div>
                    <div class="info-card-content">
                        ${medicinalBadge}
                        ${data.maladies_soignees ? `<div style="margin-top:8px"><strong>Maladies soignées :</strong> ${esc(data.maladies_soignees)}</div>` : ''}
                        ${data.posologie ? `<div style="margin-top:8px"><strong>Posologie :</strong> ${esc(data.posologie)}</div>` : ''}
                        ${data.contre_indications ? `<div style="margin-top:8px"><strong>⚠️ Contre-indications :</strong> ${esc(data.contre_indications)}</div>` : ''}
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
                
                ${locationHTML}
                
                <div class="info-card">
                    <div class="info-card-title"><i class="fa-regular fa-calendar"></i> Date du scan</div>
                    <div class="info-card-content">${new Date(data.date_scan).toLocaleString('fr-FR')}</div>
                </div>
            `;
        } else {
            body.innerHTML = `<div class="info-card"><div class="info-card-content" style="color:var(--danger)">❌ Erreur: ${esc(data.message)}</div></div>`;
        }
    } catch(err) {
        console.error('Erreur:', err);
        body.innerHTML = `<div class="info-card"><div class="info-card-content" style="color:var(--danger)">❌ Erreur de connexion au serveur</div></div>`;
    }
}

// 🔧 AJOUT : Fonction closeScanModal
function closeScanModal() {
    document.getElementById('scanModal').style.display = 'none';
}

async function loadConversations() {
    try { 
        const r = await fetch('get_chat.php?_t=' + Date.now()); 
        allConvs = await r.json(); 
        renderConvList(allConvs); 
    } catch(e) { console.error(e); }
}

function renderConvList(convs) {
    const el = document.getElementById('conv-list');
    if (!convs || !convs.length) { 
        el.innerHTML = '<div class="conv-empty"><i class="fa-regular fa-comment-dots"></i><p>Aucune conversation</p><small>Posez votre première question !</small></div>'; 
        return; 
    }
    el.innerHTML = convs.map(c => `
        <div class="conv-item ${currentConvId == c.conversation_id ? 'active' : ''}" data-conv-id="${c.conversation_id}">
            <div class="conv-item-icon"><i class="fa-solid fa-seedling"></i></div>
            <div class="conv-item-body">
                <div class="conv-item-msg">${esc(c.last_message ? c.last_message.substring(0,55) + (c.last_message.length > 55 ? '…' : '') : 'Conversation')}</div>
                <div class="conv-item-date">${fmtDate(c.last_date)}</div>
            </div>
            <button class="conv-delete-btn" onclick="event.stopPropagation(); deleteConversation('${c.conversation_id}')" title="Supprimer">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </div>
    `).join('');
}

async function deleteConversation(convId) {
    if (!confirm('Supprimer cette conversation ?')) return;
    try {
        const res = await fetch('delete_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ conversation_id: convId })
        });
        const result = await res.json();
        if (result.success) {
            toast('Conversation supprimée', 'success');
            if (currentConvId == convId) newConversation();
            loadConversations();
        } else {
            toast('Erreur lors de la suppression', 'error');
        }
    } catch(e) { toast('Erreur de connexion', 'error'); }
}

async function openConversation(convId) {
    toggleConvDrawer(); 
    switchView('accueil'); 
    clearChat(); 
    currentConvId = convId; 
    currentPlantContext = null;
    document.getElementById('chatInputArea').style.display = 'none';
    document.getElementById('agentFloating').classList.remove('moved-up');
    
    try { 
        const r = await fetch(`get_conversation.php?id=${convId}&_t=${Date.now()}`); 
        const msgs = await r.json(); 
        if (msgs && msgs.length) {
            msgs.forEach(m => { 
                if (m.role === 'user') addUser(m.content, false); 
                else if (m.role === 'assistant') addBot(esc(m.content)); 
            }); 
            hideSuggestions();
            const area = document.getElementById('chat-area');
            setTimeout(() => { area.scrollTop = area.scrollHeight; }, 100);
        } else {
            showWelcome();
        }
    } catch(e) { 
        console.error('openConversation error:', e);
        addBot('<span style="color:var(--danger)">Erreur de chargement</span>'); 
    }
}

document.getElementById('conv-list').addEventListener('click', function(e) {
    const convItem = e.target.closest('.conv-item');
    if (convItem && !e.target.closest('.conv-delete-btn')) {
        const convId = convItem.getAttribute('data-conv-id');
        if (convId) openConversation(convId);
    }
});

function clearChat() { 
    document.getElementById('chat-area').innerHTML = ''; 
}

function showWelcome() {
    document.getElementById('chat-area').innerHTML = `
    <div class="welcome-screen">
        <div class="welcome-leaf">🌿</div>
        <div class="welcome-text">
            <p class="welcome-sub">Bienvenue,</p>
            <h1 class="welcome-name">${esc(USER_NAME.split(' ')[0])} !</h1>
        </div>
        <p class="welcome-hint">Identifiez une plante en quelques secondes</p>
        <div class="action-cards">
            <div class="action-card" onclick="document.getElementById('file-input').click()">
                <div class="card-icon"><i class="fa-solid fa-cloud-upload-alt"></i></div>
                <div class="card-title">Importer une photo</div>
                <div class="card-desc">Chargez une image</div>
                <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </div>
            <div class="action-card" onclick="startCamera()">
                <div class="card-icon"><i class="fa-solid fa-camera"></i></div>
                <div class="card-title">Scanner</div>
                <div class="card-desc">Prenez une photo</div>
                <div class="card-arrow"><i class="fa-solid fa-arrow-right"></i></div>
            </div>
        </div>
    </div>`;
}

function useSuggestion(t) { 
    document.getElementById('message-input').value = t; 
    sendMsg(); 
}

function hideSuggestions() { 
    const chips = document.getElementById('sug-chips');
    if (chips) chips.style.display = 'none'; 
}

function setLoading(on) { 
    const b = document.getElementById('send-btn'); 
    if (b) {
        b.disabled = on; 
        b.innerHTML = on ? '<i class="fa-solid fa-circle-notch fa-spin"></i>' : '<i class="fa-solid fa-paper-plane"></i>'; 
    }
}

async function sendMsg() {
    const input = document.getElementById('message-input');
    const text = input.value.trim();
    if (!text) return;

    addUser(text);
    input.value = '';
    input.style.height = 'auto';
    setLoading(true);
    addTyping();

    try {
        const payload = { message: text };
        if (currentPlantContext) payload.contexte = currentPlantContext;
        
        const r = await fetch(`${API_BASE}/chat`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await r.json();
        removeTyping();

        const replyText = data.reply || "Je n'ai pas compris.";
        
        let botContent = fmtReply(replyText);
        if (data.image_generated && data.image && data.image.image_b64) {
            botContent += `
                <div style="margin-top:12px; border-radius:12px; overflow:hidden; border:1px solid #dde8d8;">
                    <img src="${data.image.image_b64}" style="width:100%; max-height:300px; object-fit:cover; display:block;" alt="Plat à base de ${esc(data.image.plante)}">
                    <div style="padding:8px 12px; background:#f0f5ee; font-size:12px; color:#2d7a3a;">
                        <i class="fa-solid fa-utensils"></i> ${esc(data.image.plante)}
                    </div>
                </div>
            `;
        }
        
        addBot(botContent);

        const saveRes = await fetch('save_chat.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ message: text, reponse: replyText, conversation_id: currentConvId }) 
        });
        const saveData = await saveRes.json();
        if (saveData.conversation_id && !currentConvId) currentConvId = saveData.conversation_id;
        await loadConversations();
        
        document.getElementById('chatInputArea').style.display = 'block';
        document.getElementById('agentFloating').classList.add('moved-up');
    } catch(e) { 
        console.error('sendMsg error:', e);
        removeTyping(); 
        addBot(`<span style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i> Erreur de connexion</span>`); 
    } finally { 
        setLoading(false); 
    }
}

function addUser(text, escape = true) { 
    const area = document.getElementById('chat-area'); 
    const div = document.createElement('div'); 
    div.className = 'msg user'; 
    div.innerHTML = `<div class="msg-bubble">${escape ? esc(text) : text}</div><div class="msg-av user">${USER_INIT}</div>`; 
    area.appendChild(div); 
    area.scrollTop = area.scrollHeight; 
}

function addBot(html) { 
    const area = document.getElementById('chat-area'); 
    const div = document.createElement('div'); 
    div.className = 'msg bot'; 
    div.innerHTML = `<div class="msg-av bot"><i class="fa-solid fa-seedling"></i></div><div class="msg-bubble">${html}</div>`; 
    area.appendChild(div); 
    area.scrollTop = area.scrollHeight; 
}

function addTyping() { 
    const area = document.getElementById('chat-area'); 
    const div = document.createElement('div'); 
    div.className = 'msg bot'; 
    div.id = 'typing'; 
    div.innerHTML = `<div class="msg-av bot"><i class="fa-solid fa-seedling"></i></div><div class="msg-bubble typing-dots"><span></span><span></span><span></span></div>`; 
    area.appendChild(div); 
    area.scrollTop = area.scrollHeight; 
}

function removeTyping() { 
    document.getElementById('typing')?.remove(); 
}

function autoResize(el) { 
    el.style.height = 'auto'; 
    el.style.height = Math.min(el.scrollHeight, 130) + 'px'; 
}

function esc(s) { 
   if (!s) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');  // ← Apostrophe échappée ici !
}
function fmtDate(d) { 
    if (!d) return ''; 
    const dt = new Date(d); 
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const dateOnly = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
    
    if (dateOnly.getTime() === today.getTime()) return "Aujourd'hui " + dt.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
    if (dateOnly.getTime() === yesterday.getTime()) return "Hier " + dt.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
    return dt.toLocaleDateString('fr-FR', {day:'numeric', month:'short', year:'numeric'});
}

function fmtReply(t) { 
    return t.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\*(.+?)\*/g, '<em>$1</em>').replace(/\n- /g, '<br>• ').replace(/\n/g, '<br>'); 
}

async function saveScanToDatabase(scanData) {
    // Normaliser maladies_detectees avant envoi :
    // L'API retourne les maladies dans sante.maladies_detectees (tableau ou string)
    // On les recopie au niveau racine pour que traitement_image.php les trouve toujours
    if (scanData.sante && scanData.sante.maladies_detectees) {
        const md = scanData.sante.maladies_detectees;
        if (Array.isArray(md) && md.length > 0) {
            scanData.maladies_detectees = md;
        } else if (typeof md === 'string' && md.trim() !== '') {
            scanData.maladies_detectees = md;
        }
    }

    try {
        const response = await fetch('traitement_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(scanData)
        });
        const result = await response.json();
        if (result.success) {
            toast('Plante identifiée et sauvegardée !', 'success');
            loadedViews.historique = false;
            loadedViews.statistiques = false;
            loadViewContent('historique');
            loadViewContent('statistiques');
        } else {
            toast('Erreur lors de la sauvegarde', 'error');
        }
    } catch(e) {
        console.error('Erreur sauvegarde:', e);
        toast('Erreur de connexion pour la sauvegarde', 'error');
    }
}

let userLocation = null;

// REMPLACER la fonction getUserLocation complète :
function getUserLocation(callback) {
    if (!navigator.geolocation) {
        console.log('Géolocalisation non supportée');
        callback(null);
        return;
    }

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            console.log('📍 Coordonnées obtenues:', lat, lng);
            
            try {
                // API OpenStreetMap directement (Nominatim)
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&accept-language=fr&addressdetails=1`);
                const data = await response.json();
                
                console.log('Réponse OpenStreetMap:', data);

                const addr = data.address || {};
                console.log('Adresse complète Nominatim:', addr);

                // Prendre uniquement ville + pays
                const ville   = addr.city || addr.town || addr.state || addr.region || '';
                const country = addr.country || '';

                console.log('Ville:', ville, '| Pays:', country);

                let fullLocation = ville && country ? `${ville}, ${country}`
                    : ville || country || 'Lieu inconnu';

                console.log('🏙️ Lieu final:', fullLocation);
                
                callback({
                    lieu: fullLocation,
                    latitude: lat,
                    longitude: lng
                });
                
            } catch (error) {
                console.error('Erreur OpenStreetMap:', error);
                callback({
                    lieu: 'Position GPS',
                    latitude: lat,
                    longitude: lng
                });
            }
        },
        (error) => {
            console.log('❌ Erreur géolocalisation:', error.message);
            callback(null);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

// REMPLACER uploadImage complète :
async function uploadImage(e) {
    const file = e.target.files[0];
    if (!file) return;
    e.target.value = '';

    const reader = new FileReader();
    reader.onload = ev => { 
        const area = document.getElementById('chat-area'); 
        const d = document.createElement('div'); 
        d.className = 'msg user'; 
        d.innerHTML = `<div class="msg-bubble img-preview-bubble"><img src="${ev.target.result}" class="preview-img"></div><div class="msg-av user">${USER_INIT}</div>`; 
        area.appendChild(d); 
        area.scrollTop = area.scrollHeight; 
    };
    reader.readAsDataURL(file);

    setLoading(true);
    addTyping();

    // Attendre la localisation (ville) AVANT tout
    getUserLocation(async (location) => {
        console.log('Location complète reçue:', location);
        
        const form = new FormData();
        form.append('image', file);
        
        // Ajouter le lieu si disponible
        if (location) {
            form.append('lieu', location.lieu);
            console.log('🏙️ Lieu ajouté au FormData:', location.lieu);
        }

        try {
            const r = await fetch(`${API_BASE}/analyze`, { 
                method: 'POST', 
                body: form 
            });
            
            const res = await r.json();
            console.log('🔍 Réponse API:', res);
            
            removeTyping();
            
            // IMPORTANT: Ajouter le lieu ET les coordonnées à la réponse pour la sauvegarde
            if (location) {
                res.lieu = location.lieu;
                res.latitude = location.latitude;
                res.longitude = location.longitude;
            }
            
            // DEBUG: Vérifier les maladies
            console.log('maladies_detectees dans réponse:', res.maladies_detectees);
            console.log('sante:', res.sante);
            console.log('sante.maladies_detectees:', res.sante?.maladies_detectees);
            
            showReport(res);
            await saveScanToDatabase(res);
            
        } catch(e) { 
            console.error('❌ uploadImage error:', e);
            removeTyping(); 
            addBot(`<span style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i> Erreur d'analyse</span>`); 
        } finally { 
            setLoading(false); 
        }
    });
}

let videoStream = null;

async function startCamera() { 
    document.getElementById('camera-modal').style.display = 'flex'; 
    try { 
        videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }); 
        document.getElementById('video').srcObject = videoStream; 
    } catch(e) { 
        document.getElementById('camera-modal').style.display = 'none'; 
        toast('Impossible d\'accéder à la caméra', 'error'); 
    } 
}
// REMPLACER capturePhoto complète :
async function capturePhoto() { 
    const v = document.getElementById('video'); 
    const c = document.createElement('canvas'); 
    c.width = v.videoWidth; 
    c.height = v.videoHeight; 
    c.getContext('2d').drawImage(v, 0, 0); 
    stopCamera(); 
    setLoading(true); 
    addTyping(); 
    
    getUserLocation(async (location) => {
        console.log('Location dans capturePhoto:', location);
        
        c.toBlob(async blob => { 
            const form = new FormData(); 
            form.append('image', blob, 'capture.jpg'); 
            
            if (location) {
                form.append('lieu', location.lieu);
                console.log('🏙️ Lieu ajouté:', location.lieu);
            }
            
            try { 
                const r = await fetch(`${API_BASE}/analyze`, { 
                    method: 'POST', 
                    body: form 
                }); 
                
                const res = await r.json();
                console.log('🔍 Réponse API capture:', res);
                
                if (location) {
                    res.lieu = location.lieu;
                    res.latitude = location.latitude;
                    res.longitude = location.longitude;
                }
                
                removeTyping(); 
                showReport(res);
                await saveScanToDatabase(res);
            } catch(e) { 
                console.error('❌ capturePhoto error:', e);
                removeTyping(); 
                addBot(`<span style="color:var(--danger)"><i class="fa-solid fa-triangle-exclamation"></i> Erreur d'analyse</span>`); 
            } finally { 
                setLoading(false); 
            } 
        }, 'image/jpeg', 0.92);
    });
}

function stopCamera() { 
    if (videoStream) videoStream.getTracks().forEach(t => t.stop()); 
    document.getElementById('camera-modal').style.display = 'none'; 
}

function showReport(r) {
    if (${r.score_confiance} < 60) {

    }
    currentPlantContext = { 
        nom_commun: r.nom_commun, 
        nom_scientifique: r.nom_scientifique 
    };

    // === SECTION SANTÉ ===
    let santeHTML = '';
    console.log('Données santé reçues:', r.sante);

    if (r.sante) {
        const etat = r.sante.etat || 'Inconnue';
        let etatIcon = 'fa-question-circle';
        let etatColor = '#7a9c7e';
        let etatText = etat;

        switch(etat.toLowerCase()) {
            case 'bonne':
                etatIcon = 'fa-check-circle';
                etatColor = '#15803d';
                etatText = 'Bonne santé';
                break;
            case 'moyenne':
                etatIcon = 'fa-circle-info';
                etatColor = '#f59e0b';
                etatText = 'Santé moyenne';
                break;
            case 'mauvaise':
                etatIcon = 'fa-exclamation-triangle';
                etatColor = '#dc2626';
                etatText = 'Mauvaise santé';
                break;
            case 'critique':
                etatIcon = 'fa-exclamation-triangle';
                etatColor = '#b91c1c';
                etatText = 'Santé critique';
                break;
            default:
                etatText = 'État inconnu';
        }

        // Gestion des maladies détectées
        let maladiesDetecteesHTML = '';
        let maladiesList = r.sante.maladies_detectees;
        
        // Normaliser en tableau
        if (typeof maladiesList === 'string') {
            maladiesList = maladiesList.split(',').map(m => m.trim()).filter(m => m);
        } else if (!Array.isArray(maladiesList)) {
            maladiesList = [];
        }
        
        // Vérifier si le tableau est vide ou contient seulement des chaînes vides
        const maladiesValides = maladiesList.filter(m => m && m.trim() !== '' && m.toLowerCase() !== 'aucune');
        
        if (maladiesValides.length > 0) {
            // Des maladies ont été détectées
            maladiesDetecteesHTML = `<p><strong>Maladies détectées :</strong> ${esc(maladiesValides.join(', '))}</p>`;
        } else {
            // Aucune maladie détectée (vérification faite)
            maladiesDetecteesHTML = `<p><strong>Maladies détectées :</strong> <span style="color: #15803d;"><i class="fa-solid fa-check"></i> Aucune maladie détectée</span></p>`;
        }

        santeHTML = `
            <div class="plant-section">
                <h3><i class="fa-solid fa-heart-pulse"></i> Santé de la plante</h3>
                <p style="color:${etatColor}; font-weight:700; font-size:15px; margin-bottom:12px;">
                    <i class="fa-solid ${etatIcon}"></i> ${esc(etatText)}
                </p>
                ${maladiesDetecteesHTML}
                ${r.sante.causes ? `<p><strong>Causes :</strong> ${esc(r.sante.causes)}</p>` : ''}
                ${r.sante.recommandations ? `
                    <p><strong>Recommandations :</strong></p>
                    <p style="margin-top:4px;">${esc(r.sante.recommandations)}</p>` : ''}
            </div>
        `;
    }

    // === SECTION RECETTES ===
    let sectionRecettes = '';
    if ((r.comestible === 'Oui' || r.comestible === 'Partiellement') && r.idees_recettes) {
        sectionRecettes = `
            <div class="plant-section">
                <h3><i class="fa-solid fa-bowl-food"></i> Idées recettes</h3>
                <p>${esc(r.idees_recettes)}</p>
                ${r.parties_comestibles ? `<p><strong>Parties comestibles :</strong> ${esc(r.parties_comestibles)}</p>` : ''}
            </div>
        `;
    }

    // === SECTION MÉDICINALE ===
    let sectionMedicinale = '';
    if (r.est_medicinale === true) {
        let maladiesListe = '';
        if (r.maladies_traitees && r.maladies_traitees.length > 0) {
            maladiesListe = `<p><strong>Maladies soignées :</strong> ${esc(r.maladies_traitees.join(', '))}</p>`;
        }
        
        sectionMedicinale = `
            <div class="plant-section">
                <h3><i class="fa-solid fa-flask-vial"></i> Propriétés médicinales</h3>
                ${maladiesListe}
                ${r.proprietes_medicinales ? `<p><strong>Propriétés :</strong> ${esc(r.proprietes_medicinales)}</p>` : ''}
                ${r.posologie ? `<p><strong>Posologie :</strong> ${esc(r.posologie)}</p>` : ''}
                ${r.contre_indications ? `<p><strong>Contre-indications :</strong> ${esc(r.contre_indications)}</p>` : ''}
            </div>
        `;
    }

    // === SECTION TOXICITÉ ===
    const niv = r.niveau_toxicite || 'Aucun';
    const dnCol = { 'Aucun':'#15803d', 'Faible':'#f59e0b', 'Moyen':'#f97316', 'Élevé':'#dc2626' }[niv] || '#7a9c7e';
    
    let sectionToxicite = `
        <div class="plant-section">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Toxicité</h3>
            <p><strong>Niveau :</strong> <span style="color:${dnCol}">${esc(r.niveau_toxicite || 'Aucun')}</span></p>
            <p><strong>Plante toxique :</strong> ${r.est_toxique ? 'Oui' : 'Non'}</p>
        </div>
    `;

    // === SECTION ENVIRONNEMENT ===
    let sectionEnvironnement = `
        <div class="plant-section">
            <h3><i class="fa-solid fa-earth-africa"></i> Impact Environnemental</h3>
            <p><strong>Plante invasive :</strong> ${r.est_invasive ? 'Oui' : 'Non'}</p>
            <p><strong>Plante allélopathique :</strong> ${r.est_allelopathique ? 'Oui' : 'Non'}</p>
            ${r.impact_environnement ? `<p><strong>Impact :</strong> ${esc(r.impact_environnement)}</p>` : ''}
        </div>
    `;

    // Construction finale du rapport
    const html = `
        <div class="plant-report-card">
            <div class="plant-header">
                ${r.image_b64 ? `<img src="${r.image_b64}" class="plant-img">` : ''}
                <div class="plant-title">
                    <h2>${esc(r.nom_commun || 'Plante inconnue')}</h2>
                    <p><em>${esc(r.nom_scientifique || '')}</em> • ${esc(r.famille || '')}</p>
                    <div class="confidence">Confiance : ${r.score_confiance || 0}%</div>
                </div>
            </div>
            
            <div class="plant-section">
                <h3><i class="fa-solid fa-leaf"></i> Description</h3>
                <p>${esc(r.description || 'Aucune description disponible')}</p>
            </div>
            
            ${santeHTML}
            ${sectionRecettes}
            ${sectionMedicinale}
            ${sectionToxicite}
            ${sectionEnvironnement}
        </div>
    `;
    

    addBot(html);
    
    const inputArea = document.getElementById('chatInputArea');
    inputArea.style.display = 'block';
    document.getElementById('agentFloating').classList.add('moved-up');
    
    setTimeout(() => {
        inputArea.scrollIntoView({ behavior: 'smooth', block: 'end' });
        document.getElementById('message-input').focus();
    }, 300);
}

function toast(msg, type) { 
    const c = document.getElementById('toasts'); 
    const t = document.createElement('div'); 
    t.className = `toast toast-${type}`; 
    t.innerHTML = `<i class="fa-solid ${type==='error'?'fa-circle-exclamation':'fa-circle-info'}"></i> ${msg}`; 
    c.appendChild(t); 
    requestAnimationFrame(() => t.classList.add('show')); 
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3200); 
}

// 🔧 AJOUT : Event listener pour fermer le modal détails en cliquant dehors
document.addEventListener('DOMContentLoaded', function() {
    const scanModal = document.getElementById('scanModal');
    if (scanModal) {
        scanModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeScanModal();
            }
        });
    }
});

// Fonction de suppression définie globalement
async function deleteScan(id, btnElement) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce scan ?')) {
        return;
    }
    
    const card = btnElement.closest('.scan-card');
    card.style.opacity = '0.5';
    
    try {
        const response = await fetch('delete_scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            card.remove();
            
            // Vérifier s'il reste des scans
            const remaining = document.querySelectorAll('.scan-card');
            if (remaining.length === 0) {
                document.getElementById('scans-grid').innerHTML = 
                    '<div class="empty-state">Aucun scan pour le moment</div>';
            }
        } else {
            alert('Erreur: ' + (data.message || 'Suppression échouée'));
            card.style.opacity = '1';
        }
    } catch (e) {
        alert('Erreur réseau');
        card.style.opacity = '1';
    }
}

loadConversations();
switchView('accueil');
</script>

<style>
.plant-report-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 16px;
}
.plant-header {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: linear-gradient(135deg, #f0f5ee, #eaf4ec);
    border-bottom: 1px solid #dde8d8;
}
.plant-img {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
}
.plant-title h2 {
    font-size: 18px;
    margin: 0 0 4px 0;
    font-family: 'Fraunces', serif;
}
.plant-title p {
    font-size: 12px;
    color: #7a9c7e;
    margin: 0;
}
.confidence {
    font-size: 11px;
    color: #2d7a3a;
    margin-top: 6px;
}
.plant-badges {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.plant-badges span {
    font-size: 10px;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 500;
}
.badge-comestible { background: #dcfce7; color: #15803d; }
.badge-partiel { background: #fef3c7; color: #d97706; }
.badge-non-comestible { background: #fee2e2; color: #dc2626; }
.badge-medicinale { background: #e0f2fe; color: #0369a1; }
.badge-toxique { background: #fef3c7; color: #d97706; }
.badge-invasive { background: #fef3c7; color: #d97706; }
.badge-allelopathique { background: #e0e7ff; color: #4338ca; }
.plant-section {
    padding: 12px 16px;
    border-bottom: 1px solid #eef2ee;
}
.plant-section h3 {
    font-size: 13px;
    font-weight: 600;
    color: #2d7a3a;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.plant-section p {
    font-size: 13px;
    margin: 4px 0;
    line-height: 1.4;
    color: #2c3e2f;
}
.plant-section strong {
    font-weight: 600;
    color: #1a4d1f;
}
.plant-section h3 i.fa-heart-pulse {
    color: #e11d48;
}

/* 🔧 AJOUT : Styles pour le modal de détails scan */
.scan-modal-overlay {
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
.scan-modal-container {
    background: white;
    border-radius: 28px;
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    animation: scanModalFadeIn 0.3s ease;
}
@keyframes scanModalFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.scan-modal-header {
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
.scan-modal-header h3 {
    font-size: 20px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.scan-modal-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    line-height: 1;
    transition: transform 0.2s;
}
.scan-modal-close-btn:hover { transform: scale(1.1); }
.scan-modal-body { padding: 24px; }

/* Styles pour les cartes d'info dans le modal */
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
.badge-oui { background: #dcfce7; color: #15803d; }
.badge-non { background: #fee2e2; color: #dc2626; }
.badge-toxique { background: #fef3c7; color: #d97706; }
.badge-medicinale { background: #e0f2fe; color: #0369a1; }
.badge-invasive { background: #fef3c7; color: #d97706; }
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
</style>

</body>
</html>