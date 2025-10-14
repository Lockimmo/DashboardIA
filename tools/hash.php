<?php
$motdepasse = 'patricia'; // remplace par ton vrai mot de passe
$hash = password_hash($motdepasse, PASSWORD_DEFAULT);
echo "Mot de passe haché :<br>$hash";