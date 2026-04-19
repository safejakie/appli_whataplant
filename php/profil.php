<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

$userId = $_SESSION['user_id'];

$user = $pdo->prepare("SELECT nom_complet, email, date_inscription FROM utilisateurs WHERE id_utilisateur = ?");
$user->execute([$userId]);
$u = $user->fetch();
?>

<div class="view-header-simple">
    <h2><i class="fa-solid fa-user-circle"></i> Mon profil</h2>
    <p>Gérez vos informations personnelles</p>
</div>

<div class="profile-card">
    <div class="profile-avatar-large">
        <?= htmlspecialchars(mb_substr($u['nom_complet'], 0, 1, 'UTF-8')) ?>
    </div>
    <div class="profile-details">
        <h3><?= htmlspecialchars($u['nom_complet']) ?></h3>
        <p><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($u['email']) ?></p>
        <p><i class="fa-regular fa-calendar"></i> Inscrit le <?= date('d/m/Y', strtotime($u['date_inscription'])) ?></p>
    </div>
</div>

<div class="profile-actions">
    <button class="edit-profile-btn" onclick="document.getElementById('editModal').style.display='flex'">
        <i class="fa-solid fa-pen"></i> Modifier mes informations
    </button>
</div>

<!-- Modal modification -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Modifier mon profil</h3>
            <button class="modal-close" onclick="document.getElementById('editModal').style.display='none'">&times;</button>
        </div>
        <form id="editProfileForm">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" name="nom_complet" value="<?= htmlspecialchars($u['nom_complet']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
            </div>
            <button type="submit" class="save-btn">Enregistrer</button>
        </form>
    </div>
</div>

<style>
.profile-card {
    background: linear-gradient(135deg, var(--s1), var(--g4));
    border-radius: 24px;
    padding: 30px;
    text-align: center;
    margin-bottom: 24px;
}
.profile-avatar-large {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--g1), var(--g2));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 42px;
    font-weight: 700;
    color: white;
}
.profile-details h3 {
    font-size: 20px;
    margin-bottom: 8px;
    color: var(--t1);
}
.profile-details p {
    font-size: 14px;
    color: var(--t3);
    margin: 8px 0;
}
.profile-actions {
    text-align: center;
}
.edit-profile-btn {
    background: var(--g1);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 40px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}
.edit-profile-btn:hover {
    background: var(--g2);
    transform: translateY(-2px);
}
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 400px;
    overflow: hidden;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: var(--g1);
    color: white;
}
.modal-header h3 {
    font-size: 18px;
    margin: 0;
}
.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
}
.modal-content form {
    padding: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: var(--t2);
}
.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--b2);
    border-radius: 10px;
    font-size: 14px;
}
.save-btn {
    width: 100%;
    background: var(--g1);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 40px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    margin-top: 5px;
}
.save-btn:hover {
    background: var(--g2);
}
</style>

<script>
document.getElementById('editProfileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Enregistrement...';
    btn.disabled = true;
    try {
        const res = await fetch('update_profile.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            alert('Profil mis à jour avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch(err) {
        alert('Erreur de connexion');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});
</script>