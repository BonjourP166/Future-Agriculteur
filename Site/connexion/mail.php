<?php
include("../bd.php"); // On utilise ton fichier de connexion BDD habituel
header('Content-Type: application/json');

// On vérifie que l'email est bien envoyé
if (isset($_POST['mail']) && !empty($_POST['mail'])) {
    
    $mail = trim($_POST['mail']);

    try {
        // On vérifie si l'email existe déjà (Ajuste 'utilisateurs' et 'email' selon ta table)
        $stmt = $bdd->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = :mail");
        $stmt->execute(['mail' => $mail]);
        $count = $stmt->fetchColumn();

        // Retour JSON : true si l'email existe, false sinon
        echo json_encode(["exists" => ($count > 0)]);
        
    } catch (Exception $e) {
        // En cas d'erreur BDD, on renvoie false par sécurité ou un message d'erreur
        echo json_encode(["exists" => false, "error" => $e->getMessage()]);
    }

} else {
    // Si pas de mail reçu dans la requête
    echo json_encode(["exists" => false]);
}
exit;
?>