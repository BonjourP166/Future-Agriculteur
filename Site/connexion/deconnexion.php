<?php
// 1. On initialise la session pour pouvoir y accéder
session_start();

// 2. On vide toutes les variables de session
$_SESSION = array();

// 4. On détruit la session côté serveur
session_destroy();

// 5. Redirection vers la page d'accueil ou de connexion
header("Location: /Agri/Future-Agriculteur/Site/index.php");
exit;
?>