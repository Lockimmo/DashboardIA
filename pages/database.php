<?php
// Sécurité : vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user']) || !isset($_SESSION['company'])) {
    header('Location: index.php?page=login');
    exit;
}

// Connexion à la BDD client
$company = $_SESSION['company'];
try {
    $dsn = "mysql:host={$company['DBServer']};dbname={$company['DBName']};charset=utf8mb4";
    $pdo_company = new PDO($dsn, $company['DBUser'], $company['DBPassword'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Erreur lors de la connexion à la base entreprise : " . $e->getMessage() . "</div>";
    exit;
}
?>

<div class="container">
    <div class="row justify-content-center align-items-start" style="min-height: 70vh;">
        <!-- Colonne gauche : Formulaire principal -->
        <div class="col-12 col-md-6 d-flex flex-column align-items-start justify-content-start mb-4 mb-md-0">

            <form id="webhookForm" class="w-100 mb-3" style="max-width:400px;">
                <div class="mb-3 text-center text-md-start">
                    <label for="inputTest" class="form-label h5">Interroger ma base de donnée</label>
                    <input type="text" class="form-control" id="inputTest" name="inputTest" placeholder="Comment faire ceci ... ?" required>
                </div>
                <div class="text-center text-md-start mb-2">
                    <button type="submit" class="btn btn-primary" id="webhookBtn">
                        <span id="webhookBtnText">Validé</span>
                        <span id="webhookBtnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="text-center text-md-start mb-2">
                    <button type="button" class="btn btn-success" id="openAddDataModal"
                    data-bs-toggle="modal" data-bs-target="#addDataModal">
                        Ajouter une donnée
                    </button>
                </div>
                <div id="webhookMessage" class="mt-2"></div>

                <!-- Carrousel des fichiers -->
                <!-- Carrousel remplacé par slider horizontal -->

<!-- Carrousel avec recherche et nombre de fichiers -->
<div id="filesCarouselWrapper" class="mt-4 w-100 d-none">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 id="filesTitle">📂 Fichiers associés (0)</h6>
        <input type="text" id="filesSearch" class="form-control form-control-sm" placeholder="Rechercher un fichier..." style="max-width:200px;">
    </div>
    <div style="position: relative;">
        <button id="carouselPrev" class="btn btn-dark position-absolute top-50 start-0 translate-middle-y" style="z-index:2;">‹</button>
        <div id="filesSlider" class="d-flex overflow-auto" style="gap: 1rem; scroll-behavior: smooth; padding: 0 2.5rem;">
            <!-- Cards générées par JS -->
        </div>
        <button id="carouselNext" class="btn btn-dark position-absolute top-50 end-0 translate-middle-y" style="z-index:2;">›</button>
    </div>
</div>
            </form>

            <!-- Modal Ajouter une donnée -->
            <div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form class="modal-content" id="addDataForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addDataModalLabel">Ajouter une donnée</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="addQuestion" class="form-label">Question *</label>
                                <input type="text" class="form-control" id="addQuestion" name="question" placeholder="Saisir la question…" required>
                            </div>
                            <div class="mb-3">
                                <label for="addReponse" class="form-label">Réponse *</label>
                                <textarea class="form-control" id="addReponse" name="reponse" rows="5" placeholder="Saisir la réponse…" required></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="addRetravailleIA" name="retravailleIA">
                                <label class="form-check-label" for="addRetravailleIA">Retravaillé IA</label>
                            </div>
                            <div id="addDataMsg" class="small"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" id="addDataSubmit">
                                <span id="addDataSubmitText">Valider</span>
                                <span id="addDataSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Colonne droite : Réponse -->
        <div class="col-12 col-md-6" id="webhookResponseContainer">
            <div id="webhookResponseWrapper" style="max-width:600px; margin:auto; border: 2px dashed #ccc; border-radius: 8px; padding: 20px; min-height: 200px; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9;">
                <div id="webhookResponsePlaceholder" class="text-muted text-center">
                    ⏳ En attente d'une requête…
                </div>
                <div id="webhookResponse" style="width:100%; display:none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
// --- Définition globale de DB_NAME ---
const DB_NAME = <?= json_encode($company['DBName']) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('webhookForm');
    const btn = document.getElementById('webhookBtn');
    const btnText = document.getElementById('webhookBtnText');
    const btnSpinner = document.getElementById('webhookBtnSpinner');

    // --- Formulaire principal ---
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const input = document.getElementById('inputTest').value.trim();
            const messageDiv = document.getElementById('webhookMessage');
            const responseDiv = document.getElementById('webhookResponse');
            const placeholderDiv = document.getElementById('webhookResponsePlaceholder');

            messageDiv.textContent = '';
            responseDiv.innerHTML = '';
            responseDiv.style.display = 'none';
            placeholderDiv.style.display = 'flex';

            if (!input) {
                messageDiv.innerHTML = '<span class="text-danger">Merci de remplir le champ.</span>';
                return;
            }

            btn.disabled = true;
            btnSpinner.classList.remove('d-none');
            btnText.textContent = 'Envoi…';

            fetch('https://hook.eu2.make.com/rwr9gagomrfbaajwxpzbpx77hil3c64j', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ message: input ,dbName: DB_NAME})
            })
            .then(async response => {
                const contentType = response.headers.get("content-type");
                let body;
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    body = await response.json();
                } else {
                    body = await response.text();
                }

                if (!response.ok) {
                    messageDiv.innerHTML = '<span class="text-danger">❌ Statut HTTP non OK ('+response.status+').</span>';
                    responseDiv.innerHTML = '<pre>' + JSON.stringify(body, null, 2) + '</pre>';
                    return;
                }

                messageDiv.innerHTML = '<span class="text-success">✅ Message envoyé avec succès !</span>';
                document.getElementById('inputTest').value = '';

                let pertinence = '';
                let htmlBody = body;
                if (typeof body === 'string') {
                    const match = body.match(/^Pertinence de la réponse *: *(\d+ ?%)\s*/i);
                    if (match) {
                        pertinence = match[1];
                        htmlBody = body.replace(/^Pertinence de la réponse *: *\d+ ?%\s*/i, '').trim();
                    }
                }

                responseDiv.innerHTML = `
                    <div class="card mt-4 shadow">
                        <div class="card-body">
                            ${pertinence ? `<span class="badge bg-info text-dark mb-3">Pertinence : ${pertinence}</span><br>` : ''}
                            <div class="mb-3 small text-secondary">Réponse IA :</div>
                            <div>${htmlBody}</div>
                        </div>
                    </div>
                `;
                responseDiv.style.display = 'block';
                placeholderDiv.style.display = 'none';
            })
            .catch(err => {
                console.error('[DEBUG] Erreur fetch :', err);
                messageDiv.innerHTML = '<span class="text-danger">❌ Une erreur est survenue lors de l\'envoi.</span>';
                responseDiv.innerHTML = '<div class="alert alert-danger">Erreur JS&nbsp;: '+err+'</div>';
                responseDiv.style.display = 'block';
                placeholderDiv.style.display = 'none';
            })
            .finally(() => {
                btn.disabled = false;
                btnSpinner.classList.add('d-none');
                btnText.textContent = 'Validé';
            });
        });
    }

    // --- Modale Ajouter une donnée ---
    const addDataForm = document.getElementById('addDataForm');
    const addDataSubmit = document.getElementById('addDataSubmit');
    const addDataSubmitText = document.getElementById('addDataSubmitText');
    const addDataSpinner = document.getElementById('addDataSpinner');
    const addDataMsg = document.getElementById('addDataMsg');

    if (addDataForm) {
        addDataForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const question = document.getElementById('addQuestion').value.trim();
            const reponse = document.getElementById('addReponse').value.trim();
            const retravailleIA = document.getElementById('addRetravailleIA').checked;

            if (!question || !reponse) {
                addDataMsg.innerHTML = '<span class="text-danger">Merci de remplir tous les champs obligatoires.</span>';
                return;
            }

            addDataMsg.innerHTML = '';
            addDataSubmit.disabled = true;
            addDataSpinner.classList.remove('d-none');
            addDataSubmitText.textContent = 'Envoi…';

            const payload = { question, reponse, retravailleIA, dbName: DB_NAME };

            fetch('https://hook.eu2.make.com/o7et7l18n3nrbz3fcdxch27s602q3txy', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            })
            .then(async response => {
                const contentType = response.headers.get("content-type");
                let body;
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    body = await response.json();
                } else {
                    body = await response.text();
                }

                if (!response.ok) {
                    addDataMsg.innerHTML = '<span class="text-danger">❌ Erreur ('+response.status+').</span>';
                    return;
                }

                addDataMsg.innerHTML = '<span class="text-success">✅ Donnée ajoutée avec succès !</span>';
                addDataForm.reset();

                // Fermeture automatique après 2s
                setTimeout(() => {
                    const modalEl = document.getElementById('addDataModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    addDataMsg.innerHTML = '';
                }, 2000);

                // Recharger le carrousel
                loadFilesCarousel();
            })
            .catch(err => {
                console.error('[DEBUG] Erreur fetch :', err);
                addDataMsg.innerHTML = '<span class="text-danger">❌ Une erreur est survenue.</span>';
            })
            .finally(() => {
                addDataSubmit.disabled = false;
                addDataSpinner.classList.add('d-none');
                addDataSubmitText.textContent = 'Valider';
            });
        });
    }

// --- Fonction pour charger le carrousel avec API-Key ---
function loadFilesCarousel() {
    const carouselWrapper = document.getElementById('filesCarouselWrapper');
    const filesSlider = document.getElementById('filesSlider');
    const prevBtn = document.getElementById('carouselPrev');
    const nextBtn = document.getElementById('carouselNext');
    const filesTitle = document.getElementById('filesTitle');
    const filesSearch = document.getElementById('filesSearch');

    fetch(`https://prod-1-data.ke.pinecone.io/assistant/files/${DB_NAME}`, {
        headers: {
            'Api-Key': 'pcsk_w1SLK_Ubc1Ut9WuwUUKT7hzGX561RUkqmSohmaCHBftE8eEBruMbyDcYJczfpbnBDhfX8'
        }
    })
    .then(resp => resp.json())
    .then(data => {
        if (!data.files || !data.files.length) {
            carouselWrapper.classList.add('d-none');
            return;
        }

        let files = data.files;
        filesTitle.textContent = `📂 Fichiers associés (${files.length})`;

        const renderCards = (list) => {
            filesSlider.innerHTML = '';
            list.forEach(file => {
                const card = document.createElement('div');
                card.className = 'card p-3';
                card.style.minWidth = '220px';
                card.style.border = '1px solid #ccc';
                card.style.backgroundColor = '#f9f9f9';
                card.innerHTML = `
                    <div class="fw-bold mb-2">${file.name}</div>
                    <div class="small text-muted mb-2">Status: ${file.status}</div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary btn-view">📄 Voir</button>
                        <button class="btn btn-sm btn-danger btn-del">🗑 Supprimer</button>
                    </div>
                `;

                // Bouton Voir
                card.querySelector('.btn-view').addEventListener('click', async () => {
                    try {
                        const resp = await fetch(`https://prod-1-data.ke.pinecone.io/assistant/files/${DB_NAME}/${file.id}?include_url=true`, {
                            headers: { 'Api-Key': 'pcsk_w1SLK_Ubc1Ut9WuwUUKT7hzGX561RUkqmSohmaCHBftE8eEBruMbyDcYJczfpbnBDhfX8' }
                        });
                        if (!resp.ok) throw new Error('Erreur API');
                        const fdata = await resp.json();
                        if (fdata.signed_url) {
                            window.open(fdata.signed_url, '_blank');
                        } else {
                            alert('❌ Impossible de récupérer le fichier.');
                        }
                    } catch (err) {
                        console.error('[DEBUG] Erreur Voir fichier:', err);
                        alert('❌ Erreur lors de l\'ouverture du fichier.');
                    }
                });

                // Bouton Supprimer
                card.querySelector('.btn-del').addEventListener('click', async () => {
                    if (!confirm(`Voulez-vous vraiment supprimer "${file.name}" ?`)) return;
                    try {
                        const resp = await fetch(`https://prod-1-data.ke.pinecone.io/assistant/files/${DB_NAME}/${file.id}`, {
                            method: 'DELETE',
                            headers: { 'Api-Key': 'pcsk_w1SLK_Ubc1Ut9WuwUUKT7hzGX561RUkqmSohmaCHBftE8eEBruMbyDcYJczfpbnBDhfX8' }
                        });
                        if (!resp.ok) throw new Error('Erreur API');
                        alert(`✅ Fichier "${file.name}" supprimé avec succès.`);
                        loadFilesCarousel(); // recharge la liste
                    } catch (err) {
                        console.error('[DEBUG] Erreur Suppression fichier:', err);
                        alert('❌ Erreur lors de la suppression.');
                    }
                });

                filesSlider.appendChild(card);
            });
        };

        renderCards(files);
        carouselWrapper.classList.remove('d-none');

        // Défilement boutons
        prevBtn.onclick = () => { filesSlider.scrollBy({left: -240, behavior: 'smooth'}); };
        nextBtn.onclick = () => { filesSlider.scrollBy({left: 240, behavior: 'smooth'}); };

        // Recherche
        filesSearch.addEventListener('input', () => {
            const query = filesSearch.value.toLowerCase();
            const filtered = files.filter(f => f.name.toLowerCase().includes(query));
            filesTitle.textContent = `📂 Fichiers associés (${filtered.length})`;
            renderCards(filtered);
        });
    })
    .catch(err => {
        console.error('[DEBUG] Erreur chargement fichiers:', err);
        carouselWrapper.classList.add('d-none');
    });
}

// Charger au démarrage
loadFilesCarousel();
});
</script>
