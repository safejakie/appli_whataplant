<?php
$host     = getenv('MYSQLHOST')          ?: 'localhost';
$dbname   = getenv('MYSQLDATABASE')      ?: 'whataplant_db';
$username = getenv('MYSQLUSER')          ?: 'root';
$password = getenv('MYSQLPASSWORD')      ?: '';
$port     = getenv('MYSQLPORT')          ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur connexion DB : " . $e->getMessage());
}
?>