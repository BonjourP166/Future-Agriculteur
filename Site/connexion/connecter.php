<?php
include("../bd.php"); // On utilise ton fichier de connexion habituel
session_start();

header('Content-Type: application/json');
$reponse = ['success' => false, 'message' => "", 'redirect' => '../index.php'];

// 1. Vérification CSRF
if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
    echo json_encode(['success' => false, 'message' => "Session expirée, veuillez rafraîchir la page."]);
    exit;
}

// 2. Récupération des données
$mail = trim($_POST['mail'] ?? '');
$mdp = trim($_POST['mdp'] ?? '');

if($mail == "" || $mdp == ""){
    $reponse['message'] = "Veuillez remplir tous les champs.";
    echo json_encode($reponse);
    exit;
} 

try {
    // 3. Recherche de l'agriculteur (Ajuste 'utilisateurs' et 'email' selon ta BDD)
    $sql = "SELECT id_user, nom, prenom, email, mdp FROM utilisateurs WHERE email = :mail";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([':mail' => $mail]);
    $user = $stmt->fetch();

    // 4. Vérification du mot de passe
    if ($user && password_verify($mdp, $user['mdp'])) {
        
        // Sécurité session
        session_regenerate_id(true);
        
        // STOCKAGE EN SESSION (Format simple)
        $_SESSION['agriculteur'] = [
            'id'     => $user['id_user'],
            'nom'    => $user['nom'],
            'prenom' => $user['prenom'],
            'email'  => $user['email']
        ];

        // On regénère un token pour la suite
        $_SESSION['token'] = bin2hex(random_bytes(32));
        
        $reponse['success'] = true;
        $reponse['message'] = "Connexion réussie !";
        
        // 5. LOGIQUE DE REDIRECTION
        if(!empty($_POST['redirect'])){
            // Protection simple contre les redirections externes malveillantes
            $target = basename(parse_url($_POST['redirect'], PHP_URL_PATH));
            
            // Si c'est une page connue, on y va, sinon index
            $allowed_pages = ['index.php', 'planning.php', 'dashboard.php', 'parcelles.php'];
            
            if (in_array($target, $allowed_pages)) {
                $query = parse_url($_POST['redirect'], PHP_URL_QUERY);
                $reponse['redirect'] = "../" . $target . ($query ? "?" . $query : "");
            } else {
                $reponse['redirect'] = "../index.php";
            }
        } else {
            $reponse['redirect'] = "../index.php";
        }

    } else {
        $reponse['message'] = "Identifiants incorrects.";
    }
} catch (Exception $e) {
    $reponse['message'] = "Erreur technique lors de la connexion.";
}

// 6. Envoi de la réponse
echo json_encode($reponse);
exit;