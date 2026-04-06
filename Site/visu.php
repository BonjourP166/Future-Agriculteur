<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'bd.php';
$bdd = getBD();

// Fetch all cultures
$sqlCulture = "SELECT id_culture, nom_culture, type_culture FROM culture ORDER BY type_culture, nom_culture";
$req = $bdd->prepare($sqlCulture);
$req->execute();
$cultures = $req->fetchAll(PDO::FETCH_ASSOC);

session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning Agricole - Optimisation ML</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="styles/nav.css">
    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/footer.css">
    <link rel="stylesheet" href="styles/visu_2.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'nav.php'; ?>

    <main>
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1>Analyse exploratoire de nos données</h1>
                <p>Découvrez les données que nous avons utilisées à l'aide de visualisations</p>
            </div>
        </section>

        
    </main>

    <?php include 'footer.php'; ?>

    <script>
       
    </script>

   
</body>
</html>