<?php
include("../bd.php");

if (!isset($bdd)) {
    echo json_encode(['success' => false, 'message' => "Erreur : La variable bdd n'est pas définie dans bd.php"]);
    exit;
}
session_start();

header('Content-Type: application/json'); 
$response = ['success' => false, 'message' => "", 'redirect' => '../index.php'];

// 1. VÉRIFICATION CSRF
if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
    $response['message'] = "Session expirée, veuillez rafraîchir la page.";
    echo json_encode($response);
    exit;
}

// 2. RÉCUPÉRATION ET NETTOYAGE DES DONNÉES
$nom    = trim($_POST['n'] ?? '');
$prenom = trim($_POST['p'] ?? '');
$adr    = trim($_POST['adr'] ?? '');
$cp     = trim($_POST['cp'] ?? '');    
$ville  = trim($_POST['ville'] ?? ''); 
$mail   = trim($_POST['mail'] ?? '');
$mdp1   = trim($_POST['mdp1'] ?? '');
$mdp2   = trim($_POST['mdp2'] ?? '');

// Validation de base
if ($nom=="" || $prenom=="" || $adr=="" || $cp=="" || $ville=="" || $mail=="" || $mdp1=="" || $mdp1 != $mdp2) {
    $response['message'] = "Veuillez remplir correctement tous les champs.";
    echo json_encode($response);
    exit;
}

// Validation format email
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = "Le format de l'adresse email est invalide.";
    echo json_encode($response);
    exit;
}

// 3. VÉRIFICATION EMAIL UNIQUE
$stmt = $bdd->prepare("SELECT id_user FROM utilisateurs WHERE email = :mail");
$stmt->execute([':mail' => $mail]);
if ($stmt->fetch()) {
    $response['message'] = "Cet email est déjà utilisé par une autre exploitation.";
    echo json_encode($response);
    exit;
}

try {
    // 4. INSERTION DANS 'UTILISATEURS'
    $sql = "INSERT INTO utilisateurs (nom, prenom, adresse, cp, ville, email, mdp, date_inscription)
            VALUES (:nom, :prenom, :adr, :cp, :ville, :mail, :mdp, NOW())";
    
    $stmt = $bdd->prepare($sql);
    $stmt->execute([
        ':nom'    => $nom,
        ':prenom' => $prenom,
        ':adr'    => $adr,
        ':cp'     => $cp,
        ':ville'  => $ville,
        ':mail'   => $mail,
        ':mdp'    => password_hash($mdp1, PASSWORD_DEFAULT)
    ]);

    $id_user = $bdd->lastInsertId();

    // 5. MISE À JOUR DE LA SESSION (Connexion automatique après inscription)
    session_regenerate_id(true);
    $_SESSION['agriculteur'] = [
        'id'     => $id_user,
        'nom'    => $nom,
        'prenom' => $prenom,
        'email'  => $mail
    ];
    
    // Nouveau token pour la session connectée
    $_SESSION['token'] = bin2hex(random_bytes(32));

    $response['success'] = true;
    $response['message'] = "Votre exploitation a été créée avec succès !";

    // 6. REDIRECTION
    if(!empty($_POST['redirect'])){
        // On nettoie la redirection pour rester en local
        $target = basename(parse_url($_POST['redirect'], PHP_URL_PATH));
        $safe_pages = ['index.php', 'planning.php', 'parcelles.php']; 

        if (in_array($target, $safe_pages)) {
            $response['redirect'] = "../" . $target;
        } else {
            $response['redirect'] = "../index.php";
        }
    } else {
        $response['redirect'] = "../index.php";
    }

} catch (Exception $e) {
    $response['message'] = "Erreur lors de la création du compte : " . $e->getMessage();
}

echo json_encode($response);