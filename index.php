<?php
require_once 'config/config.php';
require_once 'includes/header.php';

$page = $_GET['page'] ?? 'accueil';
$chemin = "pages/$page.php";

if (file_exists($chemin)) {
    include $chemin;
} else {
    echo "<h2>Page non trouvée</h2>";
}

require_once 'includes/footer.php';
?>