<?php
$host = 'projetialockimmo.cxuwgc4qwj0v.eu-west-3.rds.amazonaws.com'; // ou l'endpoint de ton RDS Aurora
$db   = 'main';      // nom de la base principale
$user = 'admin';   // ton utilisateur MySQL
$pass = 'qzsEFiTqtBVw69bZlhKS'; // mot de passe
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "❌ Erreur de connexion à la base principale : " . $e->getMessage();
    exit;
}
?>
