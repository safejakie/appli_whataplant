<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($nom) || empty($email) || empty($password)) {
        header('Location: ../index.html?error=champs_vides');
        exit;
    }

    // Vérifie si email existe déjà
    $check = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        header('Location: ../index.html?error=email_existe');
        exit;
    }

    // Hash du mot de passe
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertion
    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
    $stmt->execute([$nom, $email, $hash]);

    $userId = $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['nom'] = $nom;
    $_SESSION['email'] = $email;

    header('Location: ../tableau.php');
    exit;
}
?>