<?php
if (!isset($_SESSION['user']) || !isset($_SESSION['company'])) {
    header('Location: index.php?page=login');
    exit;
}

$company = $_SESSION['company'];
$dateFiltre = $_GET['date'] ?? null;

try {
    $dsn = "mysql:host={$company['DBServer']};dbname={$company['DBName']};charset=utf8mb4";
    $pdo = new PDO($dsn, $company['DBUser'], $company['DBPassword'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Liste objets
    $sqlList = "
        SELECT 
            o.id AS id_objet,
            o.date_creation,
            o.sources,
            o.id_object,
            o.id_status,
            o.question,
            o.question_reel,
            o.reponse_reel,
            o.reponse_ia,
            o.ref_pinecone,
            o.lien,
            s.nom_status
        FROM Objets o
        LEFT JOIN objet_status s ON o.id_status = s.id_status
        " . ($dateFiltre ? "WHERE o.date_creation >= :date" : "") . "
        ORDER BY o.date_creation DESC
    ";
    $st = $pdo->prepare($sqlList);
    if ($dateFiltre) $st->execute(['date' => $dateFiltre.' 00:00:00']); else $st->execute();
    $rows = $st->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Erreur DB : ".htmlspecialchars($e->getMessage())."</div>";
    $rows = [];
}

// Helpers
function statusBadge($nom, $id_status) {
    if ($id_status == 2) return 'warning';       // Analysé
    if ($id_status == 3) return 'success';       // Accepté
    if ($id_status == 4) return 'danger';        // Refusé
    if ($id_status == 5) return 'secondary';     // Fermé
    return 'secondary';                          // Arrivé
}

function statusTags($nom, $id_status) {
    if ($id_status == 2) return 'analyses';    // uniquement Analysé
    if ($id_status == 3) return 'acceptes';     // Analyse Accepté
    if ($id_status == 4) return 'refuses';     // Analyse Refusé
    if ($id_status == 5) return 'fermes';      // Fermé
    return 'arrives';
}

// Comptage pour filtres
$counts = ['all'=>count($rows),'arrives'=>0,'analyses'=>0,'refuses'=>0,'acceptes'=>0,'fermes'=>0];
foreach ($rows as $r) {
    $tags = statusTags($r['nom_status'] ?? '', $r['id_status']);
    foreach (explode(' ',$tags) as $tag) {
        if (isset($counts[$tag])) $counts[$tag]++;
    }
}

// Fonction pour transformer \r\n en retour à la ligne réel
function formatText($text) {
    if(!$text) return "Élément introuvable";
    return str_replace(["\\r\\n", "\\n", "\\r"], "\n", $text);
}
?>

<style>
#modalQuestion, #modalReponse, #modalReponseIA, #modalDocument {
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 400px;
    overflow-y: auto;
}
</style>

<!-- Filtres + recherche -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="btn-group" role="group" aria-label="Filtres">
        <button class="btn btn-outline-primary filter-btn active" data-filter="all">Tous (<?= $counts['all'] ?>)</button>
        <button class="btn btn-outline-info filter-btn" data-filter="arrives">Arrivés (<?= $counts['arrives'] ?>)</button>
        <button class="btn btn-outline-warning filter-btn" data-filter="analyses">Analysés (<?= $counts['analyses'] ?>)</button>
        <button class="btn btn-outline-danger filter-btn" data-filter="refuses">Refusés (<?= $counts['refuses'] ?>)</button>
        <button class="btn btn-outline-success filter-btn" data-filter="acceptes">Acceptés (<?= $counts['acceptes'] ?>)</button>
        <button class="btn btn-outline-secondary filter-btn" data-filter="fermes">Fermés (<?= $counts['fermes'] ?>)</button>
    </div>
    <input type="text" id="searchSupport" class="form-control form-control-sm ms-auto" style="max-width:260px" placeholder="🔍 Rechercher...">
</div>

<!-- Cartes -->
<div id="supportList" class="row g-3" style="max-height: 560px; overflow-y: auto;">
    <?php if (empty($rows)): ?>
        <div class="col-12 text-center text-muted">Aucun objet trouvé.</div>
    <?php else: ?>
        <?php foreach ($rows as $o): 
            $nom = $o['nom_status'] ?? 'Arrivé';
            $badge = statusBadge($nom, $o['id_status']);
            $tags = statusTags($nom, $o['id_status']);

            $title = $o['question'] ?: 'Élément introuvable';
            $question_reel = formatText($o['question_reel']);
            $reponse_reel = formatText($o['reponse_reel']);
            $reponse_ia = formatText($o['reponse_ia']);
            $lien = $o['lien'] ?: '';
        ?>
        <div class="col-12 col-sm-6 col-md-4 support-card" 
             data-tags="<?= htmlspecialchars($tags) ?>" 
             data-name="<?= htmlspecialchars(mb_strtolower($title)) ?>"
             data-id_objet="<?= $o['id_objet'] ?>">
            <div class="card h-100 shadow-sm border">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($nom) ?></span>
                        <small class="text-muted"><?= htmlspecialchars($o['date_creation']) ?></small>
                    </div>
                    <h6 class="card-title text-truncate" title="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($title) ?></h6>
                    <div class="mt-auto pt-2 d-flex justify-content-between">
                        <?php if ($lien): ?>
                            <a href="<?= htmlspecialchars($lien) ?>" target="_blank" class="btn btn-sm btn-primary">Accéder</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-secondary" disabled>Accéder</button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-secondary open-modal-btn"
                            data-question="<?= htmlspecialchars($question_reel) ?>"
                            data-reponse="<?= htmlspecialchars($reponse_reel) ?>"
                            data-reponse_ia="<?= htmlspecialchars($reponse_ia) ?>"
                            data-ref="<?= htmlspecialchars($o['ref_pinecone']) ?>"
                        ><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modale unique -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-3">
                <div class="d-flex gap-3">
                    <div class="flex-fill">
                        <h6>Question Client</h6>
                        <div id="modalQuestion" class="border p-2"></div>
                    </div>
                    <div class="flex-fill">
                        <h6>Réponse</h6>
                        <div id="modalReponse" class="border p-2"></div>
                    </div>
                    <div class="flex-fill">
                        <h6>Réponse IA</h6>
                        <div id="modalReponseIA" class="border p-2"></div>
                    </div>
                    <div class="flex-fill">
                        <h6>Document</h6>
                        <div id="modalDocument" class="border p-2 d-flex justify-content-center align-items-center">
                            <!-- Bouton injecté dynamiquement -->
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button id="closeObjectBtn" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Fermer l'objet
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    const searchInput = document.getElementById('searchSupport');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const cards = Array.from(document.querySelectorAll('.support-card'));
    let currentFilter = 'all';
    let currentObjectId = null;

    function applyFilters() {
        const q = (searchInput.value || '').toLowerCase();
        cards.forEach(card => {
            const tags = card.dataset.tags.split(' ');
            const name = card.dataset.name || '';
            const matchFilter = (currentFilter==='all') || tags.includes(currentFilter);
            const matchSearch = !q || name.includes(q);
            card.style.display = (matchFilter && matchSearch) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', applyFilters);
    filterButtons.forEach(btn => btn.addEventListener('click', () => {
        filterButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.filter;
        applyFilters();
    }));

    applyFilters();

    document.querySelectorAll('.open-modal-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            currentObjectId = btn.closest('.support-card').dataset.id_objet;
            const question = btn.dataset.question || 'Élément introuvable';
            const reponse = btn.dataset.reponse || 'Élément introuvable';
            const reponseIA = btn.dataset.reponse_ia || 'Élément introuvable';
            const ref = btn.dataset.ref || '';

            document.getElementById('modalQuestion').textContent = question;
            document.getElementById('modalReponse').textContent = reponse;
            document.getElementById('modalReponseIA').textContent = reponseIA;

            const docDiv = document.getElementById('modalDocument');
            docDiv.textContent = 'Chargement...';

            if (!ref) {
                docDiv.textContent = 'Élément introuvable';
            } else {
                try {
                    const response = await fetch(`https://prod-1-data.ke.pinecone.io/assistant/files/<?= $company['DBName'] ?>/${ref}?include_url=true`, {
                        headers: { 'Api-Key': 'pcsk_w1SLK_Ubc1Ut9WuwUUKT7hzGX561RUkqmSohmaCHBftE8eEBruMbyDcYJczfpbnBDhfX8' }
                    });
                    if (!response.ok) throw new Error('Erreur API');
                    const data = await response.json();

                    if (data.signed_url) {
                        docDiv.innerHTML = `
                            <button class="btn btn-sm btn-primary" onclick="window.open('${data.signed_url}', '_blank')">
                                📄 Ouvrir le document
                            </button>
                        `;
                    } else {
                        docDiv.textContent = 'Élément introuvable';
                    }
                } catch {
                    docDiv.textContent = 'Erreur lors du chargement du document';
                }
            }

            modal.show();
        });
    });

    // Bouton Fermer objet
    document.getElementById('closeObjectBtn').addEventListener('click', async () => {
        if (!currentObjectId) return;
        if (!confirm("Voulez-vous vraiment fermer cet objet ?")) return;

        try {
            const response = await fetch('close_object.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: currentObjectId })
            });
            const data = await response.json();

            if (data.success) {
                alert('Objet fermé avec succès !');
                location.reload();
            } else {
                alert('Erreur : ' + (data.message || 'Erreur inconnue'));
            }
        } catch (err) {
            alert('Erreur : ' + err.message);
        }
    });
});
</script>
