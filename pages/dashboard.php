<?php
session_start();
// ==========================================
// 🔒 SÉCURITÉ
// ==========================================
if (!isset($_SESSION['user']) || !isset($_SESSION['company'])) {
    header('Location: index.php?page=login');
    exit;
}

$user = $_SESSION['user'];
$company = $_SESSION['company'];
?>

<h2 class="mb-4">📊 Tableau de bord</h2>

<div class="alert alert-info">
    Connecté en tant que <strong><?= htmlspecialchars($user['Name']) ?></strong><br>
    Société : <strong><?= htmlspecialchars($company['Name']) ?></strong><br>
    Base active : <code><?= htmlspecialchars($company['DBName']) ?></code>
</div>

<?php
$dateDebut = $_GET['date_debut'] ?? null;
$dateFin   = $_GET['date_fin'] ?? null;

$arrives = $analyses = $refuses = $acceptes = 0;
$pourcentAnalyses = $pourcentAcceptes = 0;

try {
    $dsn = "mysql:host={$company['DBServer']};dbname={$company['DBName']};charset=utf8mb4";
    $pdo_company = new PDO($dsn, $company['DBUser'], $company['DBPassword'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Construire la condition de date (sur o.date_creation)
    $whereDate = "";
    $params = [];
    if ($dateDebut && $dateFin) {
        $whereDate = "WHERE o.date_creation BETWEEN :dateDebut AND :dateFin";
        $params = ['dateDebut' => $dateDebut . " 00:00:00", 'dateFin' => $dateFin . " 23:59:59"];
    } elseif ($dateDebut) {
        $whereDate = "WHERE o.date_creation >= :dateDebut";
        $params = ['dateDebut' => $dateDebut . " 00:00:00"];
    } elseif ($dateFin) {
        $whereDate = "WHERE o.date_creation <= :dateFin";
        $params = ['dateFin' => $dateFin . " 23:59:59"];
    }

    // Total Arrivés = tous les objets (tickets)
    $sqlArrive = "SELECT COUNT(*) FROM Objets o $whereDate";
    $stmt = $pdo_company->prepare($sqlArrive);
    $stmt->execute($params);
    $arrives = (int) $stmt->fetchColumn();

    // Analysés = statuts Analysé, Analyse Refusé, Analyse Accepté
    $sqlAnalyses = "
        SELECT COUNT(*) FROM Objets o
        JOIN objet_status s ON o.id_status = s.id_status
        " . ($whereDate ? $whereDate . " AND " : "WHERE ") . " s.nom_status IN ('Analysé','Analyse Refusé','Analyse Accepté')
    ";
    $stmt = $pdo_company->prepare($sqlAnalyses);
    $stmt->execute($params);
    $analyses = (int) $stmt->fetchColumn();

    // Refusés = Analyse Refusé
    $sqlRefuses = "
        SELECT COUNT(*) FROM Objets o
        JOIN objet_status s ON o.id_status = s.id_status
        " . ($whereDate ? $whereDate . " AND " : "WHERE ") . " s.nom_status = 'Analyse Refusé'
    ";
    $stmt = $pdo_company->prepare($sqlRefuses);
    $stmt->execute($params);
    $refuses = (int) $stmt->fetchColumn();

    // Acceptés = Analyse Accepté
    $sqlAcceptes = "
        SELECT COUNT(*) FROM Objets o
        JOIN objet_status s ON o.id_status = s.id_status
        " . ($whereDate ? $whereDate . " AND " : "WHERE ") . " s.nom_status = 'Analyse Accepté'
    ";
    $stmt = $pdo_company->prepare($sqlAcceptes);
    $stmt->execute($params);
    $acceptes = (int) $stmt->fetchColumn();

    // Calculs dérivés
    $nonAnalyses = max(0, $arrives - $analyses); // sécurité: jamais négatif
    $pourcentAnalyses = $arrives > 0 ? round(($analyses / $arrives) * 100, 1) : 0;
    $totalRA = $refuses + $acceptes;
    $pourcentAcceptes = $totalRA > 0 ? round(($acceptes / $totalRA) * 100, 1) : 0;

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Erreur connexion DB : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- 👀 Compteurs synthétiques -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="mb-1">📥 Arrivés</h6>
            <p class="display-6 text-primary mb-0"><?= number_format($arrives) ?></p>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="mb-1">🔍 Analysés</h6>
            <p class="display-6 text-success mb-0"><?= number_format($analyses) ?></p>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="mb-1">❌ Refusés</h6>
            <p class="display-6 text-danger mb-0"><?= number_format($refuses) ?></p>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="card shadow-sm text-center p-3">
            <h6 class="mb-1">✅ Acceptés</h6>
            <p class="display-6 text-success mb-0"><?= number_format($acceptes) ?></p>
        </div>
    </div>
</div>

<!-- 📅 FILTRE -->
<form method="get" class="row g-3 align-items-end mb-4">
    <input type="hidden" name="page" value="dashboard">

    <div class="col-auto">
        <label for="date_debut" class="form-label">📅 Du :</label>
        <input type="date" name="date_debut" id="date_debut" value="<?= htmlspecialchars($dateDebut) ?>" class="form-control">
    </div>

    <div class="col-auto">
        <label for="date_fin" class="form-label">📅 Au :</label>
        <input type="date" name="date_fin" id="date_fin" value="<?= htmlspecialchars($dateFin) ?>" class="form-control">
    </div>

    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filtrer</button>
    </div>
</form>

<!-- 📊 DEUX DONUT CHARTS CÔTE À CÔTE -->
<div class="row mt-3">
    <!-- Donut Analysés vs Non-analysés -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm p-3 text-center h-100">
            <h5>🔍 Analysés vs Non-analysés</h5>
            <canvas id="chartArriveAnalyse" style="max-height:300px;"></canvas>
            <p class="mt-2">🔍 Taux d'analyse : <strong><?= $pourcentAnalyses ?>%</strong></p>
        </div>
    </div>

    <!-- Donut Refusés vs Acceptés -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm p-3 text-center h-100">
            <h5>❌ Refusés vs ✅ Acceptés</h5>
            <canvas id="chartRefuseAccepte" style="max-height:300px;"></canvas>
            <p class="mt-2">✅ Taux acceptés : <strong><?= $pourcentAcceptes ?>%</strong></p>
        </div>
    </div>
</div>

<script>
const nonAnalyses = <?= (int)$nonAnalyses ?>;
const analyses = <?= (int)$analyses ?>;
const refuses = <?= (int)$refuses ?>;
const acceptes = <?= (int)$acceptes ?>;

// Donut Analysés vs Non-analysés
new Chart(document.getElementById('chartArriveAnalyse'), {
    type: 'doughnut',
    data: {
        labels: ['Non-analysés', 'Analysés'],
        datasets: [{
            data: [nonAnalyses, analyses],
            backgroundColor: ['#0d6efd', '#198754']
        }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom' }
        },
        maintainAspectRatio: false
    }
});

// Donut Refusés vs Acceptés
new Chart(document.getElementById('chartRefuseAccepte'), {
    type: 'doughnut',
    data: {
        labels: ['Refusés', 'Acceptés'],
        datasets: [{
            data: [refuses, acceptes],
            backgroundColor: ['#dc3545', '#198754']
        }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom' }
        },
        maintainAspectRatio: false
    }
});
</script>
