<?php
// Fichier : php/connexion.php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $mdp   = $_POST['password'];

    $query = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $query->execute([$email]);
    $user = $query->fetch();

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        $_SESSION['user_id']  = $user['id_utilisateur'];
        $_SESSION['user_nom'] = $user['nom_complet'];

        // CORRIGÉ : tableau.html (et non tableau.php)
        header("Location: tableau.php");
        exit();
    } else {
        echo "<script>alert('Email ou mot de passe incorrect'); window.history.back();</script>";
    }
}
?>