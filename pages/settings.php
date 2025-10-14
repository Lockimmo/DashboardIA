<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : redirige si non connecté
if (!isset($_SESSION['user']) || !isset($_SESSION['company'])) {
    header('Location: index.php?page=login');
    exit;
}

// Connexion à la config principale
require_once(__DIR__ . '/../config/config.php');

// Récupération du CompanyId du compte connecté
$currentCompanyId = $_SESSION['company']['Id'] ?? 0;

// Requête pour récupérer tous les utilisateurs ayant le même CompanyId
$users = [];
if ($currentCompanyId) {
    $stmt = $pdo->prepare("SELECT Id, Name, Email, CreationDate 
                           FROM Users 
                           WHERE CompanyId = :companyId");
    $stmt->execute(['companyId' => $currentCompanyId]);
    $users = $stmt->fetchAll();
}
?>

<div class="container">
    <h1 class="mb-4">⚙️ Réglages</h1>
    <p>Ici vous pourrez configurer les paramètres de votre projet IA.</p>

    <!-- Liste des utilisateurs du même compte -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5>Utilisateurs du compte</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($users)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date création</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['Id']) ?></td>
                                <td><?= htmlspecialchars($user['Name']) ?></td>
                                <td><?= htmlspecialchars($user['Email']) ?></td>
                                <td><?= htmlspecialchars($user['CreationDate']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">Aucun utilisateur trouvé pour ce compte.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Placeholder pour d'autres réglages -->
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">Section en cours de développement…</p>
        </div>
    </div>
</div>
