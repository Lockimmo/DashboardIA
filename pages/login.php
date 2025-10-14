
<?php
if (isset($_SESSION['user']) && isset($_SESSION['company'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
?>

<?php
require_once __DIR__ . '/../config/config.php';

$erreur = "";
$debug = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $motdepasse = $_POST['password'] ?? '';

    $debug .= "🔍 Tentative de connexion avec email : $email<br>";

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($motdepasse, $user['Password'])) {
        $debug .= "✅ Utilisateur trouvé : ID={$user['Id']}, CompanyId={$user['CompanyId']}<br>";
        $debug .= "✅ Mot de passe correct<br>";

        $stmt2 = $pdo->prepare("SELECT * FROM Companies WHERE Id = :companyId");
        $stmt2->execute(['companyId' => $user['CompanyId']]);
        $company = $stmt2->fetch();

        if ($company) {
            $debug .= "✅ Société trouvée : {$company['Name']}<br>";
            $debug .= "➡️ Tentative de connexion à {$company['DBServer']} / {$company['DBName']}<br>";

            try {
                $dsn = "mysql:host={$company['DBServer']};dbname={$company['DBName']};charset=utf8mb4";
                $pdo_company = new PDO($dsn, $company['DBUser'], $company['DBPassword'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                $_SESSION['user'] = $user;
                $_SESSION['company'] = $company;

                header("Location: index.php?page=dashboard");
                exit;
            } catch (PDOException $e) {
                $erreur = "❌ Connexion à la base entreprise impossible : " . $e->getMessage();
            }
        } else {
            $erreur = "❌ Société introuvable (ID={$user['CompanyId']})";
        }
    } else {
        $erreur = $user ? "❌ Mot de passe incorrect." : "❌ Utilisateur introuvable.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="mb-4 text-center">Connexion</h2>

        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if (!empty($debug)): ?>
            <div class="alert alert-secondary">
                <h5 class="mb-2">🛠 Debug</h5>
                <?= $debug ?>
            </div>
        <?php endif; ?>

        <form method="post" class="card p-4 shadow-sm">
            <div class="mb-3">
                <label for="email" class="form-label">Email :</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe :</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </div>
        </form>
    </div>
</div>