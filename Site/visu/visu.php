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
    <link rel="stylesheet" href="styles/visu.css">
    
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

        <section class="presentation-section">
            <div class="container">
                <h2>Liste de toutes nos cultures</h2>
                <?php echo "<br><br>"; ?>

                <label for="typeFilter">Filtrer par type :</label>
                <select id="typeFilter" onchange="filterCultures()">
                    <option value="all">Tous</option>
                    <option value="fruit">Fruits</option>
                    <option value="légume">Légumes</option>
                </select>

                <?php echo "<br><br>"; ?>

                <?php foreach ($cultures as $culture): ?>
                    <div class="culture-box" 
                        data-type="<?= strtolower($culture['type_culture']) ?>"
                        onclick="openModal(<?= $culture['id_culture'] ?>)">
                        <?= htmlspecialchars($culture['nom_culture']) ?>
                    </div>
                <?php endforeach; ?>

                <!-- Modal -->
                <div id="cultureModal" class="modal">
                    <div class="modal-content">
                        <span class="close-btn" onclick="closeModal()">&times;</span>
                        <div id="modal-body"></div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        function openModal(cultureId) {
            if (!cultureId || cultureId <= 0) {
                alert("ID de culture invalide !");
                return;
            }

            fetch('visu_base.php?id=' + cultureId)
                .then(resp => resp.text())
                .then(html => {
                    document.getElementById('modal-body').innerHTML = html;
                    document.getElementById('cultureModal').style.display = 'block';
                })
                .catch(err => {
                    document.getElementById('modal-body').innerHTML = "<p style='color:red;'>Erreur lors du chargement.</p>";
                    document.getElementById('cultureModal').style.display = 'block';
                    console.error(err);
                });
        }

        function closeModal() {
            document.getElementById('cultureModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('cultureModal')) {
                closeModal();
            }
        }
        function filterCultures() {
        const filter = document.getElementById('typeFilter').value;
        const boxes = document.querySelectorAll('.culture-box');

        boxes.forEach(box => {
            const type = box.getAttribute('data-type');
            if (filter === 'all' || type === filter) {
                box.style.display = 'inline-block';
            } else {
                box.style.display = 'none';
            }
        });
        }
    </script>

   
</body>
</html>