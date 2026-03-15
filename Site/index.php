<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning Agricole - Optimisation ML</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="index.css">
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
                <a href="planificateur.php" class="cta-button">Générer mon planning</a>
            </div>
        </section>

        <section class="presentation-section">
            <div class="presentation-container">
                
                <div class="presentation-intro">
                    <h2>Qui se cache derrière le projet ?</h2>
                    <div class="intro-text">
                        <p>Nous sommes un groupe de <strong>4 étudiants de l'Université Paul Valéry Montpellier 3</strong>. 
                        Dans le cadre de notre cursus en <em>Sciences des Données</em>, nous avons développé cet outil 
                        pour répondre aux défis du changement climatique en milieu agricole.</p>
                    </div>
                </div>

                <div class="valeurs-grid">
                    <div class="valeur-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Géolocalisation</h3>
                        <p>Analyse précise des sols et du climat selon votre position exacte.</p>
                    </div>
                    <div class="valeur-card">
                        <i class="fas fa-brain"></i>
                        <h3>Machine Learning</h3>
                        <p>Algorithmes prédictifs pour déterminer les dates optimales de semis.</p>
                    </div>
                    <div class="valeur-card">
                        <i class="fas fa-leaf"></i>
                        <h3>Éco-responsable</h3>
                        <p>Optimisation des ressources en eau et réduction des intrants chimiques.</p>
                    </div>
                    <div class="valeur-card">
                        <i class="fas fa-university"></i>
                        <h3>Rigueur Académique</h3>
                        <p>Projet tutoré issu du département MIASHS de Paul Valéry.</p>
                    </div>
                </div>

                <div class="presentation-cta">
                    <h3>Prêt à transformer votre exploitation ?</h3>
                    <p>Utilisez notre algorithme pour anticiper les récoltes de demain.</p>
                    <button onclick="window.location.href='planificateur.php'">Accéder au simulateur</button>
                </div>

            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>