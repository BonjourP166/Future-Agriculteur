<!DOCTYPE html>
<html lang="fr">
<?php 
include 'bd.php';
session_start();
?>
<head>
    <meta charset="UTF-8">
    <title>Planning Agricole - Algorithmes ML</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/nav.css">
    <link rel="stylesheet" href="styles/algo.css">
    <link rel="stylesheet" href="styles/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'nav.php'; ?>

    <main>

        <!-- HERO -->
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1>Prédiction de la temperature</h1>
                <p>Prédisez ci-dessous la temperature sur le court, moyen ou long terme</p>
            </div>
        </section>

        <!-- CONTENU PRINCIPAL -->
        <section class="presentation-section">

        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>