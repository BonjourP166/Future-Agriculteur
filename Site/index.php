<!DOCTYPE html>
<html lang="fr">
<?php 
include 'bd.php';
session_start();
 ?>
<head>
    <meta charset="UTF-8">
    <title>Planning Agricole - Optimisation ML</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="styles/nav.css">
    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'nav.php'; ?>

    <main>
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1>Planificateur Agricole Intelligent</h1>
                <p>Optimisez vos cycles de plantation grâce au Machine Learning et aux données géoclimatiques de votre région.</p>
                <a href="calendrier_agricole.php" class="btn-hero">Générer mon planning</a>            </div>
        </section>

        <section class="presentation-section">
            <div class="presentation-container">
                
                <div class="presentation-intro">
                    <h2>Qui se cache derrière le projet ?</h2>
                    <div class="intro-text">
                        <p>Nous sommes un groupe de <strong>4 étudiants de l'Université Paul Valéry Montpellier</strong>. 
                        Dans le cadre de notre cursus en <em>Sciences des Données</em>, nous avons développé cet outil 
                        pour répondre aux défis du changement climatique en milieu agricole. Afin de vous permettre d'optimiser vos dates de semis</p>
                    </div>
                </div>

                <div class="valeurs-grid">
                    <div class="valeur-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Géolocalisation</h3>
                        <p>Analyse précise du climat selon votre position exacte.</p>
                    </div>
                    <div class="valeur-card">
                        <i class="fas fa-brain"></i>
                        <h3>Machine Learning</h3>
                        <p>Algorithmes prédictifs pour déterminer les dates optimales de semis.</p>
                    </div>
                    
                    <div class="valeur-card">
                        <i class="fas fa-university"></i>
                        <h3>Rigueur Académique</h3>
                        <p>Projet tutoré issu du département MIASHS de l'Université Paul Valéry.</p>
                    </div>
                </div>

                <div class="presentation-cta">
                    <h3>Prêt à transformer votre exploitation ?</h3>
                    <p>Utilisez notre algorithme pour anticiper les récoltes de demain.</p>
                    <a href="calendrier_agricole.php" class="btn-principal">Générer mon planning</a>                </div>

            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>