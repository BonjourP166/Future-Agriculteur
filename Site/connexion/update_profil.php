<?php
include("../bd.php");
session_start();

// 1. Sécurité : Vérification connexion et méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['agriculteur'])) {
    header("Location: mon_compte.php");
    exit;
}

// 2. Vérification du Token CSRF
if (!isset($_POST['token_csrf']) || $_POST['token_csrf'] !== $_SESSION['token']) {
    header("Location: mon_compte.php?error=csrf");
    exit;
}

$id_user = $_SESSION['agriculteur']['id'];

// 3. RÉCUPÉRATION ET NETTOYAGE DES DONNÉES
$nom     = htmlspecialchars(trim($_POST['nom'] ?? ''));
$prenom  = htmlspecialchars(trim($_POST['prenom'] ?? ''));
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$adresse = htmlspecialchars(trim($_POST['adresse'] ?? ''));
$cp      = htmlspecialchars(trim($_POST['cp'] ?? ''));
$ville   = htmlspecialchars(trim($_POST['ville'] ?? ''));
$new_pwd = $_POST['new_password'] ?? '';

// 4. VÉRIFICATIONS
$erreurs = [];

if (empty($nom) || empty($prenom)) $erreurs[] = "Nom et prénom obligatoires.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Format d'email invalide.";

// Vérification de l'email unique (sauf pour l'utilisateur actuel)
$stmt = $bdd->prepare("SELECT id_user FROM utilisateurs WHERE email = ? AND id_user != ?");
$stmt->execute([$email, $id_user]);
if ($stmt->fetch()) {
    $erreurs[] = "Cet email est déjà utilisé par un autre compte.";
}

// Vérification du mot de passe si rempli
if (!empty($new_pwd)) {
    if (strlen($new_pwd) < 8) {
        $erreurs[] = "Le mot de passe doit faire au moins 8 caractères.";
    }
}

// Si erreurs, on redirige
if (!empty($erreurs)) {
    header("Location: mon_compte.php?error=1"); // Tu peux affiner en passant le message en session
    exit;
}

// 5. MISE À JOUR SQL
try {
    $bdd->beginTransaction();

    // Mise à jour des infos générales
    $sql = "UPDATE utilisateurs SET nom=?, prenom=?, email=?, adresse=?, cp=?, ville=? WHERE id_user=?";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([$nom, $prenom, $email, $adresse, $cp, $ville, $id_user]);

    // Mise à jour du mot de passe si fourni
    if (!empty($new_pwd)) {
        $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
        $bdd->prepare("UPDATE utilisateurs SET mdp = ? WHERE id_user = ?")->execute([$hash, $id_user]);
    }

    $bdd->commit();

    // 6. MISE À JOUR DE LA SESSION
    $_SESSION['agriculteur']['nom'] = $nom;
    $_SESSION['agriculteur']['prenom'] = $prenom;
    $_SESSION['agriculteur']['email'] = $email;

    header("Location: mon_compte.php?success=1");
    exit;

} catch (Exception $e) {
    if ($bdd->inTransaction()) { $bdd->rollBack(); }
    die("Erreur technique : " . $e->getMessage());
}