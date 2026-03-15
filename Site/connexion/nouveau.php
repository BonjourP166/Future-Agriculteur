<?php
    include("../bd.php");
    session_start(); // Ne pas oublier pour les tokens et sessions


    if (!isset($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }

    $redirect = $_GET['redirect'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte</title>
    
    <link rel="stylesheet" href="../styles/nav.css">
    <link rel="stylesheet" href="../styles/connexion.css">
    <link rel="stylesheet" href="../styles/footer.css">
            <link rel="stylesheet" href="../styles/index.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    const redirect = "<?= htmlspecialchars($redirect) ?>";
    
    let validNom = false;
    let validPrenom = false;
    let validAdr = false;
    let validMail = false;
    let validMdp1 = false;
    let validMdp2 = false;
    let validCP = false;
    let validVille = false;

    $(document).ready(function() {
        function validationNomPrenom(val){
            val = val.trim();
            return typeof val=="string" && val.length>1;
        }
        
        function validationAdresse(val){
            val = val.trim();
            return val.length>1;
        }
        
        function validationCP(val) {
            val = val.trim();
            return val.length >= 3 && val.length <= 10;
        }

        function validationVille(val) {
            return val.trim().length > 1;
        }

        function validationMdp(val){
            val=val.trim();
            if (val.length < 8) return false;
            let lettre=0, chiffre=0, caractSpe=0;
            for (let i = 0; i < val.length; i++) {
                const c = val[i];
                if ((c >= 'A' && c <= 'Z') || (c >= 'a' && c <= 'z')) lettre++;
                else if (c >= '0' && c <= '9') chiffre++; 
                else caractSpe++; 
            }
            return lettre > 0 && chiffre > 0 && caractSpe > 0;
        }

        function validationConfirmationMdp(val){
            const mdp1 = $("#mdp1").val().trim();
            return val.trim() === mdp1 && val.trim() !== "";
        }

        function affichageReponse($input,valide,message){
            let $reponse = $input.next('.msg');
            if ($reponse.length == 0) {
                $reponse = $('<div class="msg"></div>');
                $input.after($reponse);
            }
            if (!valide) {
                $input.addClass('error');
                $reponse.text(message).addClass('error-msg-inline');
            } else {
                $input.removeClass('error').addClass('valid');
                $reponse.text('').removeClass('error-msg-inline'); 
            }
        }
    
        $('input[name=n]').blur(function(){ validNom = validationNomPrenom($(this).val()); affichageReponse($(this),validNom,"Champ obligatoire"); });
        $('input[name=p]').blur(function(){ validPrenom = validationNomPrenom($(this).val()); affichageReponse($(this),validPrenom,"Champ obligatoire"); });
        $('input[name=adr]').blur(function(){ validAdr = validationAdresse($(this).val()); affichageReponse($(this),validAdr,"Adresse obligatoire"); });
        $('input[name=cp]').blur(function(){ validCP = validationCP($(this).val()); affichageReponse($(this), validCP, "Code postal requis"); });
        $('input[name=ville]').blur(function(){ validVille = validationVille($(this).val()); affichageReponse($(this), validVille, "Ville requise"); });
        
        $('input[name=mail]').blur(function(){
            const val = $(this).val().trim();
            const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailReg.test(val)) {
                affichageReponse($(this), false, "Email invalide");
                validMail=false;
            } else {
                validMail=true; // Simplifié : pas d'AJAX mail ici selon ta demande
                affichageReponse($(this), true, "");
            }
        });
        
        $('input[name=mdp1]').blur(function(){ validMdp1 = validationMdp($(this).val()); affichageReponse($(this),validMdp1,"8 caractères min. + lettre, chiffre et symbole"); });
        $('input[name=mdp2]').blur(function(){ validMdp2 = validationConfirmationMdp($(this).val()); affichageReponse($(this),validMdp2,"Les mots de passe ne correspondent pas"); });
    });
    
    function verifTout(event){
        event.preventDefault();
        if(!(validNom && validPrenom && validAdr && validCP && validVille && validMail && validMdp1 && validMdp2)){
            alert("Veuillez corriger les champs en rouge.");
            return;
        }

        let formData = $('#formulaire').serializeArray();
        if(redirect) formData.push({name: 'redirect', value: redirect});

        $.ajax({
            url: 'enregistrement.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response){
                if(response.success){
                    $('#message').addClass('success-msg').text(response.message).show();
                    setTimeout(function(){ window.location.href = response.redirect; },1000);
                } else {
                    $('#message').addClass('error-msg').text(response.message).show();
                }
            }
        });
    }
    </script>
</head>
<body>
    <?php include '../nav.php'; ?>

    <main class="inscription-page">
        <div class="inscription-container">
            <div class="inscription-header">
                <h1>Créer un compte</h1>
                <p>Optimisez votre exploitation avec AgriPredict</p>
            </div>

            <div class="form-card">
                <form id="formulaire" method="post">
                    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">

                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Informations personnelles</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nom">Nom <span class="required">*</span></label>
                                <input type="text" id="nom" name="n">
                            </div>
                            <div class="form-group">
                                <label for="prenom">Prénom <span class="required">*</span></label>
                                <input type="text" id="prenom" name="p">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Localisation (France)</h3>
                        <div class="form-group">
                            <label for="adresse">Adresse complète <span class="required">*</span></label>
                            <input type="text" id="adresse" name="adr">
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cp">Code postal <span class="required">*</span></label>
                                <input type="text" id="cp" name="cp" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label for="ville">Ville <span class="required">*</span></label>
                                <input type="text" id="ville" name="ville">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-envelope"></i> Sécurité</h3>
                        <div class="form-group">
                            <label for="email">Email professionnel <span class="required">*</span></label>
                            <input type="text" id="email" name="mail">
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="mdp1">Mot de passe <span class="required">*</span></label>
                                <input type="password" id="mdp1" name="mdp1">
                            </div>
                            <div class="form-group">
                                <label for="mdp2">Confirmation <span class="required">*</span></label>
                                <input type="password" id="mdp2" name="mdp2">
                            </div>
                        </div>
                    </div>

                    <div id="message" class="form-message"></div>
                    <input type="button" value="Créer mon compte" onclick="verifTout(event)" class="btn-submit">
                </form>

                <div class="form-footer">
                    <p>Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../footer.php'; ?>
</body>
</html>