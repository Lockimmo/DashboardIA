<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageActuelle = $_GET['page'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Projet IA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Barre de navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Projet IA</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= ($pageActuelle === 'dashboard' ? ' active' : '') ?>" href="index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($pageActuelle === 'database' ? ' active' : '') ?>" href="index.php?page=database">Base de donnée</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($pageActuelle === 'support' ? ' active' : '') ?>" href="index.php?page=support">Support Client</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= ($pageActuelle === 'settings' ? ' active' : '') ?>" href="index.php?page=settings">Réglages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=logout">Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link<?= ($pageActuelle === 'login' ? ' active' : '') ?>" href="index.php?page=login">Connexion</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container">