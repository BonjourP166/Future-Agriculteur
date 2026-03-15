<?php
session_start();
include("../bd.php"); 

// 1. Sécurité : On vérifie que l'agriculteur est bien connecté
if (!isset($_SESSION['agriculteur'])) {
    header("Location: ../index.php");
    exit();
}

$id_user = $_SESSION['agriculteur']['id'];

try {
    $bdd->beginTransaction();

    // 2. Suppression de l'utilisateur
    // Note : Si tu as d'autres tables liées (ex: parcelles, récoltes), 
    // assure-toi d'avoir mis "ON DELETE CASCADE" en SQL ou supprime les ici.
    $stmt = $bdd->prepare("DELETE FROM utilisateurs WHERE id_user = ?");
    $stmt->execute([$id_user]);

    $bdd->commit();

    // 3. Destruction propre de la session
    $_SESSION = array();

    // Effacement du cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, 
            $params["path"], 
            $params["domain"], 
            $params["secure"], 
            $params["httponly"]
        );
    }

    session_destroy();

    // 4. Redirection vers l'accueil avec un message
    header("Location: ../index.php?status=deleted");
    exit();

} catch (Exception $e) {
    if ($bdd->inTransaction()) { 
        $bdd->rollBack(); 
    }
    // En cas d'erreur, on évite d'afficher des détails techniques à l'utilisateur
    exit("Une erreur est survenue lors de la suppression de votre compte.");
}