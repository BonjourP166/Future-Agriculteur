<?php
include("../bd.php");
session_start();

if (!isset($_SESSION['agriculteur'])) {
    header("Location: connexion.php");
    exit;
}

$id_user = $_SESSION['agriculteur']['id'];
$stmt = $bdd->prepare("SELECT nom, prenom, email, adresse, cp, ville FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - AgriPredict</title>
    <link rel="stylesheet" href="../styles/nav.css">
    <link rel="stylesheet" href="../styles/mon_compte.css">
        <link rel="stylesheet" href="../styles/index.css">

    <link rel="stylesheet" href="../styles/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../nav.php'; ?>

    <main class="account-page">
        <div class="account-container">
            
            <div class="account-header">
                <h1>Mon Compte</h1>
                <p>Gérez vos accès et les données de votre exploitation</p>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert-success">Vos informations ont été mises à jour.</div>
            <?php endif; ?>

            <section class="account-card">
                <h2 class="section-title">Informations de l'exploitation</h2>
                <form action="update_profil.php" method="POST">
                    <input type="hidden" name="token_csrf" value="<?= $_SESSION['token'] ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Email professionnel</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Adresse complète</label>
                            <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Code Postal</label>
                            <input type="text" name="cp" value="<?= htmlspecialchars($user['cp']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Ville</label>
                            <input type="text" name="ville" value="<?= htmlspecialchars($user['ville']) ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Changer le mot de passe <span class="hint">(Laisser vide si inchangé)</span></label>
                            <input type="password" name="new_password" placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn-save">Enregistrer les modifications</button>
                </form>
            </section>

            <section class="account-card delete-card">
                <h2 class="section-title">Suppression du compte</h2>
                <p>Cette action est irréversible et supprimera toutes vos données.</p>
                <a href="supprimer_compte.php" class="link-delete" onclick="return confirm('Confirmer la suppression définitive ?');">
                    Supprimer mon compte
                </a>
            </section>

            <div class="account-footer">
                <a href="../index.php" class="btn-back">Retour à l'accueil</a>
            </div>

        </div>
    </main>

    <?php include '../footer.php'; ?>
</body>
</html>