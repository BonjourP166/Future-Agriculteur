<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('bd.php');
$bdd = getBD();

// Get culture ID from GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("<p style='color:red;'>ID de culture invalide.</p>");
}

// Fetch culture
$sqlCulture = "SELECT nom_culture, type_culture FROM culture WHERE id_culture = ?";
$req = $bdd->prepare($sqlCulture);
$req->execute([$id]);
$culture = $req->fetch(PDO::FETCH_ASSOC);
if (!$culture) die("<p style='color:red;'>Culture introuvable.</p>");

// Fetch soils
$sqlSoil = "
SELECT s.soil_name, s.texture_class, s.drainage, s.irrigation_frequency
FROM adapter a
JOIN type_sol s ON s.soil_id = a.soil_id
WHERE a.id_culture = ?";
$req = $bdd->prepare($sqlSoil);
$req->execute([$id]);
$soils = $req->fetchAll(PDO::FETCH_ASSOC);

// Fetch temperature & season
$sqlTemps = "
SELECT st.mois_semis, st.mois_recolte, st.saison, st.remarque,
       ct.t_opt_germination, ct.t_ideale_croissance
FROM saisonnalite st
LEFT JOIN contrainte_temperature ct ON ct.id_culture = st.id_culture
WHERE st.id_culture = ?";


$req = $bdd->prepare($sqlTemps);
$req->execute([$id]);
$temps = $req->fetchAll(PDO::FETCH_ASSOC);

// Display
echo "<h3>{$culture['nom_culture']} ({$culture['type_culture']})</h3>";

echo "<br>";

echo "<h4>Température & Saison</h4>";
if (!$temps) {
    echo "<p>Aucune information sur la saison et la température.</p>";
} else {
    echo "<table border='1' cellpadding='5'>
            <tr><th>Mois de Semis</th><th>Mois de Récolte</th><th>Saison</th><th>Remarque</th><th>Temp. de Germination</th><th>Temp. de Croissance</th></tr>";
    foreach($temps as $t){
        echo "<tr>
                <td>{$t['mois_semis']}</td>
                <td>{$t['mois_recolte']}</td>
                <td>{$t['saison']}</td>
                <td>{$t['remarque']}</td>
                <td>{$t['t_opt_germination']}</td>
                <td>{$t['t_ideale_croissance']}</td>
              </tr>";
    }
    echo "</table>";
}

echo "<br>";

echo "<h4>Sol adapté</h4>";
if (!$soils) {
    echo "<p>Aucun sol adapté trouvé.</p>";
} else {
    echo "<table border='1' cellpadding='5'>
            <tr><th>Nom du Sol</th><th>Texture</th><th>Drainage</th><th>Irrigation</th></tr>";
    foreach($soils as $s){
        echo "<tr>
                <td>{$s['soil_name']}</td>
                <td>{$s['texture_class']}</td>
                <td>{$s['drainage']}</td>
                <td>{$s['irrigation_frequency']}</td>
              </tr>";
    }
    echo "</table>";
}


?>