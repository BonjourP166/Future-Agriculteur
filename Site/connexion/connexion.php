<?php
// 1. Initialisation de la session
session_start();
include("../bd.php"); // On utilise ton fichier de base de données

// 2. Génération d'un token CSRF si non présent
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// 3. LA REDIRECTION (Si déjà connecté)
if (isset($_SESSION['agriculteur'])) {
    $redirect = $_GET['redirect'] ?? '../index.php';
    header("Location: $redirect");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    
    <link rel="stylesheet" href="../styles/connexion.css"> 
    <link rel="stylesheet" href="../styles/nav.css">
    <link rel="stylesheet" href="../styles/footer.css">
            <link rel="stylesheet" href="../styles/index.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function connexion(){
        let formData = $("#formulaire_connexion").serializeArray();
        const urlParams = new URLSearchParams(window.location.search);
        const redirect = urlParams.get('redirect');
        
        if(redirect) formData.push({name: 'redirect', value: redirect});
        
        $.ajax({
            url: 'connecter.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(reponse) {
                const msgBox = $('#message');
                msgBox.removeClass('error-msg success-msg').hide();
                
                if (reponse.success) {
                    msgBox.addClass('success-msg').text(reponse.message).fadeIn();
                    setTimeout(function() {
                        window.location.href = reponse.redirect;
                    }, 1000);
                } else {
                    msgBox.addClass('error-msg').text(reponse.message).fadeIn();
                }
            }
        });
    }
    
    $(document).ready(function() {
        $("#formulaire_connexion").on("submit", function(e) {
            e.preventDefault();
            connexion();
        });
    });
    </script>
</head>
<body>
    <?php include '../nav.php'; ?>

    <main class="connexion-page">
        <div class="connexion-container">
            
            <div class="connexion-header">
                <h1><i class="fas fa-leaf" style="color: #27ae60;"></i> Planning Agricole</h1>
                <p>Connectez-vous pour acceder à votre Espace de planification intelligente</p>
            </div>

            <div class="form-card">
                <form id="formulaire_connexion" autocomplete="on">
                    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">

                    <div id="message" class="form-message"></div>

                    <div class="form-group">
                        <label for="email">Adresse Email</label>
                        <input type="email" id="email" name="mail" placeholder="agriculteur@montpellier.fr" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="mdp" required>
                    </div>

                    <button type="submit" class="btn-primary">Accéder au planificateur</button>

                    <div class="form-divider">
                        <span>Nouveau sur la plateforme ?</span>
                    </div>

                    <a href="nouveau.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" class="btn-secondary">
                        <i class="fas fa-plus-circle"></i> Créer une exploitation
                    </a>
                </form>
            </div>
        </div>
    </main>

    <?php include '../footer.php'; ?>
</body>
</html>