<?php
// On autorise suffisamment de mémoire
ini_set('memory_limit', '512M');
include 'bd.php';
session_start();

$bdd->query("SET lc_time_names = 'fr_FR'");

// ══════════════════════════════════════════════
// 1. DÉTAIL TEMPÉRATURE
// ══════════════════════════════════════════════
$query_line = "
    SELECT YEAR(date) AS annee, DATE_FORMAT(date, '%M') AS date_label, ROUND(AVG(temperature - 273.15), 1) AS temp
    FROM releve_meteo WHERE temperature IS NOT NULL 
    GROUP BY YEAR(date), MONTH(date), DAY(date), HOUR(date), DATE_FORMAT(date, '%M')
    ORDER BY YEAR(date), MONTH(date), DAY(date), HOUR(date)
";
$res_line = $bdd->query($query_line);
$data_annee = []; $annees_disponibles = [];
while ($row = $res_line->fetch(PDO::FETCH_ASSOC)) {
    $a = (string)$row['annee'];
    if (!isset($data_annee[$a])) { $annees_disponibles[] = $a; $data_annee[$a] = ['labels' => [], 'temps' => []]; }
    $data_annee[$a]['labels'][] = ucfirst($row['date_label']);
    $data_annee[$a]['temps'][] = (float)$row['temp'];
}
sort($annees_disponibles);
$js_data_temp = json_encode($data_annee);
$js_annees_temp = json_encode($annees_disponibles);
unset($data_annee);

// ══════════════════════════════════════════════
// 2. ÉVOLUTION ANNUELLE MÉTÉO
// ══════════════════════════════════════════════
$query_annuelle = "
    SELECT YEAR(date) as annee, 
           ROUND(AVG(temperature - 273.15), 1) as t_moy, ROUND(AVG(humidite), 1) as h_moy,
           ROUND(AVG(temperature_min_sol_12h - 273.15), 1) as sol_moy, ROUND(AVG(precipitations_dernieres_24h), 2) as p_moy
    FROM releve_meteo WHERE temperature IS NOT NULL GROUP BY YEAR(date) ORDER BY YEAR(date)
";
$res_annuelle = $bdd->query($query_annuelle);
$evol_annuelle = ['annees'=>[], 't'=>[], 'h'=>[], 'sol'=>[], 'p'=>[]];
while($row = $res_annuelle->fetch(PDO::FETCH_ASSOC)) {
    $evol_annuelle['annees'][] = $row['annee'];
    $evol_annuelle['t'][] = (float)$row['t_moy'];
    $evol_annuelle['h'][] = (float)$row['h_moy'];
    $evol_annuelle['sol'][] = (float)$row['sol_moy'];
    $evol_annuelle['p'][] = (float)$row['p_moy'];
}
$js_evol_annuelle = json_encode($evol_annuelle);

// ══════════════════════════════════════════════
// 3. SAISONS
// ══════════════════════════════════════════════
$query_saison = "
    SELECT 
        CASE 
            WHEN MONTH(date) IN (3,4,5)   THEN 'Printemps'
            WHEN MONTH(date) IN (6,7,8)   THEN 'Été'
            WHEN MONTH(date) IN (9,10,11) THEN 'Automne'
            ELSE 'Hiver' 
        END AS saison,
 
        -- TEMPÉRATURE : moyenne, et percentiles 10/90 pour éviter les extrêmes
        ROUND(AVG(temperature - 273.15), 1)                                     AS t_avg,
        ROUND(AVG(temperature - 273.15) - STDDEV(temperature - 273.15)*1.5, 1) AS t_min,
        ROUND(AVG(temperature - 273.15) + STDDEV(temperature - 273.15)*1.5, 1) AS t_max,
 
        -- HUMIDITÉ
        ROUND(AVG(humidite), 1)                                     AS h_avg,
        ROUND(AVG(humidite) - STDDEV(humidite)*1.5, 1)             AS h_min,
        ROUND(AVG(humidite) + STDDEV(humidite)*1.5, 1)             AS h_max,
 
        -- PRÉCIPITATIONS : somme totale / nombre de jours distincts = mm/jour moyen
        ROUND(
            SUM(precipitations_dernieres_24h) / COUNT(DISTINCT DATE(date))
        , 2) AS p_avg,
        0    AS p_min,
        ROUND(
            MAX(precipitations_dernieres_24h)
        , 2) AS p_max
 
    FROM releve_meteo
    WHERE temperature IS NOT NULL
    GROUP BY saison
";
$res_saison = $bdd->query($query_saison);
$ordre_saisons = ['Printemps', 'Été', 'Automne', 'Hiver'];
$saisons_data = array_fill_keys($ordre_saisons, null);
while($row = $res_saison->fetch(PDO::FETCH_ASSOC)) { $saisons_data[$row['saison']] = $row; }
$js_saisons = json_encode(array_values($saisons_data));

// ══════════════════════════════════════════════
// 4. HEATMAP MENSUELLE & CORRÉLATION (DYNAMIQUE)
// ══════════════════════════════════════════════
$query_mois = "
    SELECT MONTH(date) as mois, AVG(temperature - 273.15) as t, AVG(humidite) as h, 
           AVG(temperature_min_sol_12h - 273.15) as s, AVG(precipitations_dernieres_24h) as p
    FROM releve_meteo GROUP BY MONTH(date) ORDER BY MONTH(date)
";
$res_mois = $bdd->query($query_mois);
$raw_mois = ['t'=>[], 'h'=>[], 's'=>[], 'p'=>[]];
while($row = $res_mois->fetch(PDO::FETCH_ASSOC)) {
    $raw_mois['t'][] = $row['t']; $raw_mois['h'][] = $row['h']; $raw_mois['s'][] = $row['s']; $raw_mois['p'][] = $row['p'];
}
function normalize($arr) {
    if (empty($arr)) return [];
    $min = min($arr); $max = max($arr);
    return array_map(function($v) use ($min, $max) { return $max == $min ? 0 : round(($v - $min) / ($max - $min), 2); }, $arr);
}
$heatmap_data = [
    'Température' => normalize($raw_mois['t']), 'Humidité' => normalize($raw_mois['h']),
    'Temp. sol' => normalize($raw_mois['s']), 'Précipitations' => normalize($raw_mois['p'])
];
$js_heatmap = json_encode($heatmap_data);

// >>> NOUVEAU : CALCUL DE LA CORRÉLATION DE PEARSON EN PHP <<<
function pearson_correlation($x, $y) {
    $n = count($x);
    if ($n == 0) return 0;
    $mean_x = array_sum($x) / $n;
    $mean_y = array_sum($y) / $n;
    $num = 0; $den_x = 0; $den_y = 0;
    for ($i = 0; $i < $n; $i++) {
        $dx = $x[$i] - $mean_x;
        $dy = $y[$i] - $mean_y;
        $num += $dx * $dy;
        $den_x += $dx * $dx;
        $den_y += $dy * $dy;
    }
    $den = sqrt($den_x * $den_y);
    return $den == 0 ? 0 : $num / $den;
}
$corr_vars = ['Temp.' => $raw_mois['t'], 'Humidité' => $raw_mois['h'], 'Temp. sol' => $raw_mois['s'], 'Précip.' => $raw_mois['p']];
$corr_matrix = [];
foreach($corr_vars as $data1) {
    $row = [];
    foreach($corr_vars as $data2) {
        $row[] = pearson_correlation($data1, $data2);
    }
    $corr_matrix[] = $row;
}
$js_corr_matrix = json_encode($corr_matrix);


// ══════════════════════════════════════════════
// 5. SCATTER PRÉCIPITATIONS / HUMIDITÉ
// ══════════════════════════════════════════════
$query_scatter = "SELECT humidite, precipitations_dernieres_24h as precip FROM releve_meteo WHERE precipitations_dernieres_24h > 0 AND humidite IS NOT NULL LIMIT 300";
$res_scatter = $bdd->query($query_scatter);
$scatter_ph = [];
while($row = $res_scatter->fetch(PDO::FETCH_ASSOC)) { $scatter_ph[] = ['x'=>(float)$row['precip'], 'y'=>(float)$row['humidite']]; }
$js_scatter_ph = json_encode($scatter_ph);

// ══════════════════════════════════════════════
// 6. SEUIL SOL
// ══════════════════════════════════════════════
$query_sol = "
    SELECT DATE_FORMAT(date, '%Y-%m-%d') as d, ROUND(AVG(temperature_min_sol_12h - 273.15), 1) as temp_sol 
    FROM releve_meteo 
    WHERE temperature_min_sol_12h IS NOT NULL 
    GROUP BY DATE_FORMAT(date, '%Y-%m-%d') 
    ORDER BY DATE_FORMAT(date, '%Y-%m-%d')
";
$res_sol = $bdd->query($query_sol);
$sol_dates = []; $sol_vals = []; $compteur = 0;
while($row = $res_sol->fetch(PDO::FETCH_ASSOC)) {
    if ($compteur % 5 === 0) { $sol_dates[] = $row['d']; $sol_vals[] = (float)$row['temp_sol']; }
    $compteur++;
}
$js_sol_dates = json_encode($sol_dates); $js_sol_vals = json_encode($sol_vals);

// ══════════════════════════════════════════════
// 7. DIAGRAMME DE GANTT (SAISONNALITÉ)
// ══════════════════════════════════════════════
$query_gantt = "
    SELECT c.nom_culture as nom, s.mois_semis as semis, s.mois_recolte as recolte
    FROM culture c
    JOIN saisonnalite s ON c.id_culture = s.id_culture
    WHERE s.mois_semis IS NOT NULL AND s.mois_recolte IS NOT NULL
";
$res_gantt = $bdd->query($query_gantt);
$gantt_data = [];
while($row = $res_gantt->fetch(PDO::FETCH_ASSOC)) {
    $gantt_data[] = [
        'nom' => ucfirst(strtolower($row['nom'])),
        'semis' => (int)$row['semis'],
        'recolte' => (int)$row['recolte']
    ];
}
$js_gantt_data = json_encode($gantt_data);

// ══════════════════════════════════════════════
// 8. RÉPARTITION DES CULTURES (PIE)
// ══════════════════════════════════════════════
$query_pie = "
    SELECT type_culture, COUNT(*) as nb 
    FROM culture 
    WHERE type_culture IS NOT NULL AND type_culture != ''
    GROUP BY type_culture
";
$res_pie = $bdd->query($query_pie);
$pie_labels = []; $pie_data = [];
while($row = $res_pie->fetch(PDO::FETCH_ASSOC)) {
    $pie_labels[] = ucfirst(strtolower($row['type_culture']));
    $pie_data[] = (int)$row['nb'];
}
$js_pie_labels = json_encode($pie_labels);
$js_pie_data = json_encode($pie_data);

// ══════════════════════════════════════════════
// 9. CONTRAINTES THERMIQUES
// ══════════════════════════════════════════════
$query_cultures_temp = "
    SELECT c.nom_culture, ct.t_ideale_croissance as t_ideale
    FROM culture c
    JOIN contrainte_temperature ct ON c.id_culture = ct.id_culture
    WHERE ct.t_ideale_croissance IS NOT NULL
    ORDER BY ct.t_ideale_croissance ASC
";
$res_ct = $bdd->query($query_cultures_temp);
$cultures_temp_data = [];
while($row = $res_ct->fetch(PDO::FETCH_ASSOC)) {
    $cultures_temp_data[] = [
        'nom' => ucfirst(strtolower($row['nom_culture'])),
        't_ideale' => (float)$row['t_ideale']
    ];
}
$js_cultures_temp = json_encode($cultures_temp_data);

// ══════════════════════════════════════════════
// 10. ANALYSE DES SOLS
// ══════════════════════════════════════════════
$query_sols = "
    SELECT soil_name as nom, sand_pct as sable, clay_pct as argile
    FROM type_sol
    WHERE sand_pct IS NOT NULL AND clay_pct IS NOT NULL
";
$res_sols = $bdd->query($query_sols);
$sols_data = [];
while($row = $res_sols->fetch(PDO::FETCH_ASSOC)) {
    $sols_data[] = [
        'nom' => ucfirst(strtolower($row['nom'])),
        'sable' => (float)$row['sable'],
        'argile' => (float)$row['argile']
    ];
}
$js_sols_data = json_encode($sols_data);

$sqlCulture = "SELECT id_culture, nom_culture, type_culture FROM culture ORDER BY type_culture, nom_culture";
$req = $bdd->prepare($sqlCulture);
$req->execute();
$cultures = $req->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Statistiques Agricoles - Visualisations</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link rel="stylesheet" href="styles/nav.css">
<link rel="stylesheet" href="styles/visu.css">
<link rel="stylesheet" href="styles/visu_2.css">
<link rel="stylesheet" href="styles/footer.css">
</head>
<body>
<?php include 'nav.php'; ?>

<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Analyse statistique des données agricoles</h1>
        <p>Explorez les visualisations climatiques, agronomiques et décisionnelles issues de notre base de données</p>
    </div>
</section>

<div class="page-body">
<div class="container">

<div class="section-title"><i class="fas fa-cloud-sun"></i> Analyse exploratoire climatique</div>

<div class="card">
    <div class="card-title"><i class="fas fa-chart-bar"></i> Évolution annuelle des conditions météorologiques</div>
    <div class="chart-wrap"><canvas id="chartEvolAnnuelle"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> On note une augmentation progressive de l'humidité moyenne entre 2022 et 2025 ainsi que des variations de température et de précipitations, traduisant une instabilité interannuelle du climat.</p>
</div>

<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:15px;">
        <div class="card-title" style="margin:0;"><i class="fas fa-thermometer-half"></i> <span id="titreGraphiqueTemp">Évolution de la température</span></div>
        <div class="tabs" id="tabsAnneeTemp" style="margin-bottom:0;">
            <?php foreach($annees_disponibles as $index => $annee): ?>
                <button class="tab-btn <?= $index === 0 ? 'active' : '' ?>" onclick="switchAnneeTemp(this, '<?= htmlspecialchars($annee) ?>')"><?= htmlspecialchars($annee) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="chart-wrap" style="height: 350px;"><canvas id="temperatureChart"></canvas></div>
        <p class="card-obs"><strong>Observation :</strong> Cette vue détaillée permet d'observer l'ensemble des variations à haute fréquence de l'année sélectionnée, tout en conservant une lisibilité trimestrielle sur l'axe des abscisses.</p>

</div>

<div class="card">
    <div class="card-title"><i class="fas fa-chart-line"></i> Évolution annuelle de la température moyenne</div>
    <div class="chart-wrap"><canvas id="chartEvolTemp"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> Hausse de 15,9°C (2022) à 16,2°C (2023), suivie d'une baisse à 14,8°C (2025), soit plus d'1°C de variation en deux ans.</p>
</div>

<div class="section-title"><i class="fas fa-leaf"></i> Analyse saisonnière</div>
<p class="section-subtitle">Les conditions climatiques diffèrent selon les saisons et impactent la planification agricole.</p>

<div class="grid-3">
    <div class="card"><div class="card-title"><i class="fas fa-thermometer"></i> Température (°C)</div><div class="chart-wrap"><canvas id="chartSaisonTemp"></canvas></div></div>
    <div class="card"><div class="card-title"><i class="fas fa-tint"></i> Humidité (%)</div><div class="chart-wrap"><canvas id="chartSaisonHum"></canvas></div></div>
    <div class="card"><div class="card-title"><i class="fas fa-cloud-rain"></i> Précipitations (mm)</div><div class="chart-wrap"><canvas id="chartSaisonPrecip"></canvas></div></div>
</div>
<div class="card" style="margin-top:0">
    <p class="card-obs" style="border:none;margin:0;padding:0"><strong>Observation :</strong> Précipitations médianes proches de 0 mm en été, nettement plus élevées en automne. Humidité médiane dépassant 80 % en hiver contre ~70 % en été. Les extrêmes de précipitations (>300 mm) apparaissent en hiver.</p>
</div>

<div class="section-title"><i class="fas fa-th"></i> Carte de chaleur des conditions climatiques mensuelles</div>
<p class="section-subtitle">Valeurs normalisées de 0 à 1 — permet de comparer l'évolution relative de chaque variable sur l'année.</p>

<div class="card">
    <div class="card-title"><i class="fas fa-fire"></i> Heatmap mensuelle normalisée</div>
    <div style="overflow-x:auto"><table class="heatmap-table" id="heatmapTable"></table></div>
    <p class="card-obs"><strong>Observation :</strong> Températures et températures du sol au maximum en juillet-août (≈1,00), précipitations et humidité maximales en novembre-février. Opposition nette été/hiver pour chaque variable.</p>

</div>

<div class="section-title"><i class="fas fa-project-diagram"></i> Relations entre variables climatiques</div>
<div class="grid-2">
    <div class="card">
        <div class="card-title"><i class="fas fa-network-wired"></i> Corrélation entre variables météo</div>
        <table class="corr-table" id="corrTable"></table>
        <p class="card-obs"><strong>Observation :</strong> Valeurs dynamiques calculées par algorithme de Pearson. Les variables proches de 1 sont fortement liées.</p>
    </div>
    <div class="card">
        <div class="card-title"><i class="fas fa-dot-circle"></i> Précipitations vs Humidité</div>
        <div class="chart-wrap"><canvas id="chartScatterPrecip"></canvas></div>
            <p class="card-obs"><strong>Observation :</strong> Très forte corrélation positive température / température sol (0,99). Humidité et précipitations positivement liées (0,68), négativement liées à la température.</p>

    </div>
</div>

<div class="card">
    <div class="card-title"><i class="fas fa-thermometer-quarter"></i> Seuil optimal de plantation — Température minimale du sol (°C)</div>
    <div class="chart-wrap-tall"><canvas id="chartSeuilSol"></canvas></div>
        <p class="card-obs"><strong>Observation :</strong> Le seuil de 10°C est franchi chaque année au printemps et maintenu jusqu'à fin d'été. Il passe largement en dessous en hiver, définissant des fenêtres d'impossibilité de plantation.</p>

</div>

<div class="section-title"><i class="fas fa-seedling"></i> Analyse agronomique</div>

<div class="card">
    <div class="card-title"><i class="fas fa-chart-pie"></i> Répartition des cultures selon leur type</div>
    <div class="pie-grid">
        <div class="chart-wrap" style="height:240px"><canvas id="chartPieCultures"></canvas></div>
        <ul class="legend-list">
    <?php 
    $pie_colors = ['#27ae60', '#3498db', '#e67e22', '#9b59b6', '#f1c40f'];
    $total_cultures = array_sum($pie_data);
    foreach($pie_labels as $idx => $label): 
        $pct = $total_cultures > 0 ? round(($pie_data[$idx] / $total_cultures) * 100, 1) : 0;
    ?>
        <li>
            <span class="legend-dot" style="background:<?= $pie_colors[$idx % count($pie_colors)] ?>"></span>
            <span class="legend-label"><?= htmlspecialchars($label) ?></span>
            <span class="legend-pct"><?= $pct ?> %</span>
        </li>
    <?php endforeach; ?>
</ul>
    </div>
        <p class="card-obs"><strong>Observation :</strong> Déséquilibre en faveur des légumes (70 % vs 30 %), à prendre en compte dans la construction des modèles de recommandation.</p>

</div>

<div class="section-title"><i class="fas fa-calendar-alt"></i> Planning agricole — Semis et récoltes</div>
<p class="section-subtitle">Fenêtres de croissance et de récolte pour chaque culture.</p>

<div class="card">
    <div class="card-title"><i class="fas fa-tasks"></i> Diagramme de Gantt — Semis et récoltes par culture</div>
    <div style="display:flex;gap:16px;margin-bottom:14px">
        <span style="display:flex;align-items:center;gap:6px;font-size:0.83rem"><span style="display:inline-block;width:18px;height:12px;background:#97d0dc;border-radius:2px;border:1px solid #ccc"></span> Croissance / Semis</span>
        <span style="display:flex;align-items:center;gap:6px;font-size:0.83rem"><span style="display:inline-block;width:18px;height:12px;background:#b2d8b2;border-radius:2px;border:1px solid #ccc"></span> Récolte</span>
    </div>
    <div class="gantt-wrap"><svg id="ganttSvg" class="gantt-svg"></svg></div>
    <p class="card-obs"><strong>Observation :</strong> Certaines cultures ont une longue fenêtre de croissance (tomate, nectarine jusqu'en octobre), d'autres très courte (épinard, navet). Cette visualisation guide directement le calendrier de plantation.</p>

</div>

<div class="section-title"><i class="fas fa-fire-alt"></i> Profils thermiques et composition des sols</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Températures idéales de croissance</div>
        <div class="chart-wrap-tall"><canvas id="chartTop10"></canvas></div>
        <p class="card-obs"><strong>Observation :</strong> Écart de ~14°C entre cultures chaudes (pastèque ≈30°C) et froides (épinard ≈16°C). Confirmation d'exigences thermiques très hétérogènes.</p>

    </div>
    <div class="card">
        <div class="card-title">Adéquation climatique (Base 12.1°C)</div>
        <div class="chart-wrap-tall"><canvas id="chartAdequation"></canvas></div>
            <p class="card-obs"><strong>Observation :</strong> Les cultures nécessitant >25°C ont un fort écart avec la moyenne française (12,1°C). Les cultures entre 16–18°C restent les mieux adaptées.</p>

    </div>
</div>

<div class="card">
    <div class="card-title"><i class="fas fa-layer-group"></i> Clustering thermique des cultures agricoles</div>
    <div class="cluster-badges">
        <span class="badge badge-2">Cluster 0 — Fraîches (<21°C)</span>
        <span class="badge badge-0">Cluster 1 — Tempérées (21–24°C)</span>
        <span class="badge badge-1">Cluster 2 — Chaudes (>24°C)</span>
    </div>
    <div class="chart-wrap"><canvas id="chartClusterCultures"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> Trois groupes bien séparés confirment que les recommandations doivent être différenciées selon le profil thermique de chaque culture.</p>

</div>

<div class="card">
    <div class="card-title"><i class="fas fa-cubes"></i> Clustering des sols selon leur composition</div>
    <div class="cluster-badges">
        <span class="badge badge-1">Sols sableux (>55% Sable)</span>
        <span class="badge badge-2">Intermédiaires</span>
        <span class="badge badge-0">Sols argileux (>30% Argile)</span>
    </div>
    <div class="chart-wrap"><canvas id="chartSolCluster"></canvas></div>
        <p class="card-obs"><strong>Observation :</strong> Trois profils clairs : sols très sableux à faible argile, sols intermédiaires, et sols argileux à forte rétention d'eau. Ces profils conditionnent directement les besoins en irrigation.</p>

</div>

</div></div>

<section class="presentation-cultures">
            <div class="container">
                <h2>Liste de toutes nos cultures</h2> <p>Cliquez dessus pour avoir plus de détails</p>
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


<?php include 'footer.php'; ?>      
<script>
const MOIS = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
const SAISONS = ['Printemps','Été','Automne','Hiver'];
const C = ['#27ae60','#2c3e50','#3498db','#e67e22','#e74c3c','#9b59b6','#1abc9c','#f39c12'];
const baseOptions = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ labels:{ font:{ family:'Inter', size:12 }, color:'#546e7a' } } } };

// DONNÉES MÉTÉO PHP
const evolAnnuelle = <?= $js_evol_annuelle ?>;
const dataSaisons = <?= $js_saisons ?>;
const dataHeatmap = <?= $js_heatmap ?>;
const corrMatrix = <?= $js_corr_matrix ?>; // LA CORRELATION !
const scatterPh = <?= $js_scatter_ph ?>;
const solDates = <?= $js_sol_dates ?>;
const solVals = <?= $js_sol_vals ?>;

// DONNÉES AGRONOMIQUES PHP
const pieLabels = <?= $js_pie_labels ?>;
const pieData = <?= $js_pie_data ?>;
const ganttData = <?= $js_gantt_data ?>;
const culturesTemp = <?= $js_cultures_temp ?>;
const solsData = <?= $js_sols_data ?>;

// ─────────────────────────────────────────────
// GRAPHIQUES MÉTÉO
// ─────────────────────────────────────────────
new Chart(document.getElementById('chartEvolAnnuelle'), { type:'bar', data:{ labels: evolAnnuelle.annees, datasets:[ { label:'Température (°C)', data:evolAnnuelle.t, backgroundColor:'rgba(39,174,96,0.75)' }, { label:'Humidité (%)', data:evolAnnuelle.h, backgroundColor:'rgba(52,152,219,0.7)' }, { label:'Temp. sol (°C)', data:evolAnnuelle.sol, backgroundColor:'rgba(44,62,80,0.65)' }, { label:'Précipitations (mm)', data:evolAnnuelle.p, backgroundColor:'rgba(230,126,34,0.7)' } ] }, options:{ ...baseOptions } });
new Chart(document.getElementById('chartEvolTemp'), { type:'line', data:{ labels: evolAnnuelle.annees, datasets:[{ label:'Température moyenne annuelle (°C)', data:evolAnnuelle.t, borderColor:'#27ae60', backgroundColor:'rgba(39,174,96,0.12)', fill:true, tension:0.3 }] }, options:{ ...baseOptions, plugins:{ legend:{display:false} } } });
const rawDataTemp = <?= $js_data_temp ?>; const anneesTemp = <?= $js_annees_temp ?>; let lineChartTemp = null;
function switchAnneeTemp(btn, selectedYear) { document.querySelectorAll('#tabsAnneeTemp .tab-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); updateTempChart(selectedYear); }
function updateTempChart(selectedYear) { const yearData = rawDataTemp[selectedYear]; document.getElementById('titreGraphiqueTemp').innerText = "Évolution de la température - " + selectedYear; if (lineChartTemp) { lineChartTemp.data.labels = yearData.labels; lineChartTemp.data.datasets[0].data = yearData.temps; lineChartTemp.update(); } else { lineChartTemp = new Chart(document.getElementById('temperatureChart').getContext('2d'), { type: 'line', data: { labels: yearData.labels, datasets: [{ label: 'Température (°C)', data: yearData.temps, borderColor: '#1f77b4', borderWidth: 1.2, pointRadius: 0, tension: 0 }] }, options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 5, font: { size: 14, weight: 'bold' } } } } } }); } }
if (anneesTemp.length > 0) updateTempChart(anneesTemp[0]);
function makeSaisonChart(canvasId, type, color, yLabel, yMin, yMax) {
    const avgs = dataSaisons.map(s => s ? parseFloat(s[type + '_avg']) : null);
    const mins = dataSaisons.map(s => s ? parseFloat(s[type + '_min']) : null);
    const maxs = dataSaisons.map(s => s ? parseFloat(s[type + '_max']) : null);
 
    new Chart(document.getElementById(canvasId), {
        type: 'bar',
        data: {
            labels: SAISONS,
            datasets: [{
                label: 'Moyenne',
                data: avgs,
                backgroundColor: color + 'bb',
                borderColor: color,
                borderWidth: 2,
                borderRadius: 5,
                barPercentage: 0.55,
                categoryPercentage: 0.75
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => SAISONS[items[0].dataIndex],
                        label: (item) => ` Moyenne : ${item.parsed.y} ${yLabel.split(' ')[0]}`,
                        afterBody: (items) => {
                            const i = items[0].dataIndex;
                            return [
                                `  ↓ Min typ. : ${mins[i] ?? '—'}`,
                                `  ↑ Max typ. : ${maxs[i] ?? '—'}`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#546e7a',
                        font: { family: 'Inter', size: 11 },
                        align: 'center',
                        maxRotation: 0,
                        minRotation: 0
                    }
                },
                y: {
                    // Si yMin/yMax fournis → axe fixe, sinon Chart.js s'adapte
                    ...(yMin !== null && { min: yMin }),
                    ...(yMax !== null && { max: yMax }),
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: {
                        color: '#546e7a',
                        font: { family: 'Inter', size: 11 }
                    },
                    title: {
                        display: true,
                        text: yLabel,
                        color: '#546e7a',
                        font: { family: 'Inter', size: 11 }
                    }
                }
            }
        }
    });
}
 
// Température : axe fixe -5 à 35°C (valeurs réalistes Montpellier)
makeSaisonChart('chartSaisonTemp',   't', '#27ae60', 'Température (°C)',    0,  35);
 
// Humidité : toujours entre 0 et 100%
makeSaisonChart('chartSaisonHum',    'h', '#3498db', 'Humidité (%)',         0, 100);
 
// Précipitations : min à 0, max dynamique (Chart.js s'adapte à tes données)
makeSaisonChart('chartSaisonPrecip', 'p', '#2c3e50', 'Précipitations (mm/j)', 0, null);
function hmColor(v) { const stops = [[255,255,230],[180,230,190],[100,190,160],[50,140,200],[20,80,160]]; const idx = v * (stops.length - 1); const lo = Math.floor(idx), hi = Math.min(lo+1, stops.length-1); const t = idx - lo; const r = Math.round(stops[lo][0] + t*(stops[hi][0]-stops[lo][0])); const g = Math.round(stops[lo][1] + t*(stops[hi][1]-stops[lo][1])); const b = Math.round(stops[lo][2] + t*(stops[hi][2]-stops[lo][2])); return {bg:`rgb(${r},${g},${b})`, tc: (0.299*r+0.587*g+0.114*b)>160 ? '#2c3e50' : '#ffffff'}; }
const tbl = document.getElementById('heatmapTable'); let hdr = '<thead><tr><th>Variable</th>'; MOIS.forEach(m => hdr += `<th>${m.substring(0,3)}</th>`); hdr += '</tr></thead><tbody>';
for (const [varName, vals] of Object.entries(dataHeatmap)) { hdr += `<tr><th style="background:var(--primary);color:white;text-align:left;padding-left:10px">${varName}</th>`; vals.forEach(v => { const {bg,tc} = hmColor(v); hdr += `<td style="background:${bg};color:${tc}">${v.toFixed(2)}</td>`; }); hdr += '</tr>'; } tbl.innerHTML = hdr + '</tbody>';

// GÉNÉRATION DE LA TABLE DE CORRELATION EN JS
const corrLabels = ['Temp.','Humidité','Temp. sol','Précip.'];
function corrColor(v) {
    if(v>=0.9) return '#1a6b3c'; if(v>=0.7) return '#27ae60'; if(v>=0.4) return '#82c9a0';
    if(v>=-0.4) return '#546e7a'; if(v>=-0.7) return '#e8a87c'; if(v>=-0.9) return '#e74c3c'; return '#a93226';
}
const ct = document.getElementById('corrTable');
let ch = '<thead><tr><th></th>'; corrLabels.forEach(l => ch += `<th>${l}</th>`); ch += '</tr></thead><tbody>';
corrMatrix.forEach((row,i) => {
    ch += `<tr><th style="background:var(--primary);color:white">${corrLabels[i]}</th>`;
    row.forEach(v => { ch += `<td style="color:${corrColor(v)}">${v.toFixed(2)}</td>`; });
    ch += '</tr>';
});
ch += '</tbody>'; ct.innerHTML = ch;


new Chart(document.getElementById('chartScatterPrecip'), { type:'scatter', data:{ datasets:[{ label:'Relevés', data:scatterPh, backgroundColor:'rgba(39,174,96,0.25)', pointRadius:3 }] }, options:{ ...baseOptions, plugins:{ legend:{display:false} }, scales:{ x:{ title:{display:true,text:'Précipitations (mm)'} }, y:{ title:{display:true,text:'Humidité (%)'} } } } });
new Chart(document.getElementById('chartSeuilSol'), { type:'line', data:{ labels: solDates, datasets:[ { label:'Temp. minimale du sol (°C)', data:solVals, borderColor:'#2c3e50', backgroundColor:'rgba(44,62,80,0.07)', pointRadius:0, tension:0.4, fill:true }, { label:'Seuil germination (10°C)', data:Array(solDates.length).fill(10), borderColor:'#e74c3c', borderDash:[6,4], borderWidth:2, pointRadius:0, fill:false } ] }, options:{ ...baseOptions, scales:{ x:{ ticks:{ maxTicksLimit:12 } } } } });

// ─────────────────────────────────────────────
// GRAPHIQUES AGRONOMIQUES ET SOLS
// ─────────────────────────────────────────────
new Chart(document.getElementById('chartPieCultures'), {
    type:'doughnut',
    data:{ labels: pieLabels, datasets:[{ data: pieData, backgroundColor: ['#27ae60', '#3498db', '#e67e22', '#9b59b6', '#f1c40f'], borderWidth:2, borderColor:'#fff' }] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: ctx => `${ctx.label}: ${ctx.raw}` } } } }
});

ganttData.sort((a,b) => a.semis - b.semis);
const svg = document.getElementById('ganttSvg');
const ROW = 22, PAD_L = 130, PAD_T = 30, PAD_R = 20, COL_W = 42;
const W = PAD_L + COL_W*12 + PAD_R;
const H = PAD_T + ROW * ganttData.length + 30; 
svg.setAttribute('viewBox',`0 0 ${W} ${H}`);
svg.setAttribute('height', H);
let svgHTML = `<rect x="0" y="0" width="${W}" height="${PAD_T}" fill="#2c3e50" rx="8"/>`;
['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'].forEach((m,i)=>{ svgHTML += `<text x="${PAD_L+COL_W*i+COL_W/2}" y="19" text-anchor="middle" fill="white" font-size="11" font-family="Inter">${m}</text>`; });
for(let m=0;m<12;m++){ svgHTML += `<line x1="${PAD_L+COL_W*m}" y1="${PAD_T}" x2="${PAD_L+COL_W*m}" y2="${H}" stroke="#e0e0e0" stroke-width="0.5"/>`; }
ganttData.forEach((c,i)=>{
    const y = PAD_T + ROW*i;
    svgHTML += `<rect x="0" y="${y}" width="${W}" height="${ROW}" fill="${i%2===0 ? '#fafafa' : '#ffffff'}"/>`;
    svgHTML += `<text x="${PAD_L-8}" y="${y+15}" text-anchor="end" fill="#2c3e50" font-size="10.5" font-family="Inter">${c.nom}</text>`;
    const s = c.semis-1, r = c.recolte-1;
    if(s<=r){
        const bw = (r-s)*COL_W;
        if(bw>0) svgHTML += `<rect x="${PAD_L+s*COL_W+1}" y="${y+4}" width="${bw-1}" height="${ROW-8}" fill="#97d0dc" rx="3"/>`;
        svgHTML += `<rect x="${PAD_L+r*COL_W+1}" y="${y+4}" width="${COL_W-2}" height="${ROW-8}" fill="#b2d8b2" rx="3"/>`;
    } else {
        const bw1 = (12-s)*COL_W;
        if(bw1>0) svgHTML += `<rect x="${PAD_L+s*COL_W+1}" y="${y+4}" width="${bw1-1}" height="${ROW-8}" fill="#97d0dc" rx="3"/>`;
        if(r>0) svgHTML += `<rect x="${PAD_L+1}" y="${y+4}" width="${r*COL_W-1}" height="${ROW-8}" fill="#97d0dc" rx="3"/>`;
        svgHTML += `<rect x="${PAD_L+r*COL_W+1}" y="${y+4}" width="${COL_W-2}" height="${ROW-8}" fill="#b2d8b2" rx="3"/>`;
    }
});
svg.innerHTML = svgHTML;

const topLabels = culturesTemp.map(c => c.nom);
const topVals = culturesTemp.map(c => c.t_ideale);
const topColors = topVals.map(v => v < 20 ? 'rgba(52,152,219,0.78)' : (v > 24 ? 'rgba(231,76,60,0.78)' : 'rgba(39,174,96,0.78)'));

new Chart(document.getElementById('chartTop10'), { 
    type:'bar', 
    data:{ labels:topLabels, datasets:[{ label:'T° idéale (°C)', data:topVals, backgroundColor:topColors }] }, 
    options:{ ...baseOptions, indexAxis:'y', plugins:{ legend:{display:false} } } 
});

const tempFR = 12.1;
const adeqData = culturesTemp.map(c => ({ x: c.t_ideale, y: c.t_ideale - tempFR, label: c.nom }));
new Chart(document.getElementById('chartAdequation'), { 
    type:'scatter', 
    data:{ datasets:[{ label:'Cultures', data: adeqData, backgroundColor: topColors, pointRadius:7 }] }, 
    options:{ ...baseOptions, plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: (ctx) => `${ctx.raw.label} — T° idéale: ${ctx.raw.x}°C | Écart: +${ctx.raw.y.toFixed(1)}°C` } } }, scales:{ x:{ title:{display:true,text:'T° idéale (°C)'} }, y:{ title:{display:true,text:'Écart avec moyenne France (°C)'} } } } 
});

const clustFraiches = [], clustTemperees = [], clustChaudes = [];
culturesTemp.forEach(c => {
    const point = { x: c.t_ideale, y: 0, label: c.nom, r: 8 };
    if (c.t_ideale < 21) { point.y = 0 + (Math.random()-0.5)*2; clustFraiches.push(point); }
    else if (c.t_ideale > 24) { point.y = 2 + (Math.random()-0.5)*2; clustChaudes.push(point); }
    else { point.y = 1 + (Math.random()-0.5)*2; clustTemperees.push(point); }
});

new Chart(document.getElementById('chartClusterCultures'), { 
    type:'bubble', 
    data:{ datasets:[ 
        { label:'Fraîches', data:clustFraiches, backgroundColor:'rgba(52,152,219,0.7)' },   // Bleu
        { label:'Tempérées', data:clustTemperees, backgroundColor:'rgba(39,174,96,0.7)' }, // Vert
        { label:'Chaudes', data:clustChaudes, backgroundColor:'rgba(231,76,60,0.7)' }      // Rouge
    ] }, 
    options:{ ...baseOptions, plugins:{ tooltip:{ callbacks:{ label: ctx => ctx.raw.label+' — '+ctx.raw.x.toFixed(1)+'°C' } } }, scales:{ y:{ min:-1, max:3, ticks:{ display:false } }, x:{ title:{display:true,text:'T° idéale (°C)'} } } } 
});

const sols0 = [], sols1 = [], sols2 = [];
solsData.forEach(s => {
    const point = { x: s.sable, y: s.argile, label: s.nom, r: 8 };
    if (s.sable >= 55) sols0.push(point); 
    else if (s.argile >= 30) sols2.push(point); 
    else sols1.push(point); 
});

new Chart(document.getElementById('chartSolCluster'), { 
    type:'bubble', 
    data:{ datasets:[ { label:'Sableux', data:sols0, backgroundColor:'rgba(230,126,34,0.7)' }, { label:'Intermédiaire', data:sols1, backgroundColor:'rgba(52,152,219,0.7)' }, { label:'Argileux', data:sols2, backgroundColor:'rgba(39,174,96,0.7)' } ] }, 
    options:{ ...baseOptions, plugins:{ tooltip:{ callbacks:{ label: ctx => `${ctx.raw.label} (Sable: ${ctx.raw.x}%, Argile: ${ctx.raw.y}%)` } } }, scales:{ x:{ min:0, max:100, title:{display:true,text:'% Sable'} }, y:{ min:0, max:60, title:{display:true,text:'% Argile'} } } } 
});
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