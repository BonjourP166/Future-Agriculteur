<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Statistiques Agricoles - Visualisations</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap');

:root {
    --primary: #2c3e50;
    --accent: #27ae60;
    --accent-dark: #1e8449;
    --text-dark: #2c3e50;
    --text-medium: #546e7a;
    --text-light: #90a4ae;
    --bg-light: #f8faf9;
    --bg-white: #ffffff;
    --border: #cfd8dc;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 10px 25px rgba(0,0,0,0.08);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    /* Palette charts */
    --c1: #27ae60; --c2: #2c3e50; --c3: #3498db;
    --c4: #e67e22; --c5: #e74c3c; --c6: #9b59b6;
    --c7: #1abc9c; --c8: #f39c12;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; color: var(--text-dark); background: var(--bg-white); }

/* HERO */
.hero {
    width: 100%; height: 70vh; min-height: 500px;
    position: relative; display: flex; align-items: center; justify-content: center;
    text-align: center; color: white;
    background: url('/images/champs-agriculture.jpg') center/cover no-repeat;
    overflow: hidden;
}
.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(44,62,80,0.85) 0%, rgba(39,174,96,0.65) 100%);
    z-index: 2;
}
.hero-content { position: relative; z-index: 3; max-width: 900px; padding: 0 20px; animation: fadeInUp 1s ease-out; }
@keyframes fadeInUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
.hero-content h1 { font-family:'Montserrat',sans-serif; font-size:clamp(2.2rem,5vw,3.8rem); font-weight:700; line-height:1.2; margin-bottom:20px; }
.hero-content p { font-size:clamp(1.1rem,2vw,1.4rem); font-weight:300; opacity:0.9; }

/* LAYOUT */
.page-body { background: var(--bg-light); padding: 70px 20px 100px; }
.container { max-width: 1100px; margin: 0 auto; }

/* SECTION HEADERS */
.section-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.7rem; font-weight: 700;
    color: var(--primary); margin: 60px 0 8px;
    display: flex; align-items: center; gap: 12px;
}
.section-title::before {
    content:''; display:inline-block; width:5px; height:1.3em;
    background:var(--accent); border-radius:3px; flex-shrink:0;
}
.section-subtitle { font-size:0.95rem; color:var(--text-medium); margin-bottom:28px; max-width:750px; font-style:italic; }

/* CARDS */
.card {
    background: var(--bg-white); border: 1px solid var(--border);
    border-radius: 16px; padding: 28px 32px; margin-bottom: 28px;
    box-shadow: var(--shadow-sm); transition: var(--transition);
}
.card:hover { box-shadow: var(--shadow-md); border-color: var(--accent); }
.card-title {
    font-size: 1rem; font-weight: 600; color: var(--primary);
    margin-bottom: 6px; display: flex; align-items: center; gap: 8px;
}
.card-title i { color: var(--accent); font-size: 0.9rem; }
.card-obs { font-size: 0.85rem; color: var(--text-medium); margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border); font-style: italic; }
.card-obs strong { color: var(--accent-dark); font-style: normal; }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; }

/* CHART CONTAINERS */
.chart-wrap { position: relative; height: 280px; }
.chart-wrap-tall { position: relative; height: 420px; }
.chart-wrap-gantt { position: relative; height: 520px; }

/* HEATMAP */
.heatmap-table { width:100%; border-collapse:collapse; font-size:0.78rem; margin-top:8px; }
.heatmap-table th { background:var(--primary); color:white; padding:7px 4px; text-align:center; font-weight:500; font-size:0.75rem; }
.heatmap-table th:first-child { text-align:left; padding-left:10px; min-width:160px; }
.heatmap-table td { padding:6px 4px; text-align:center; font-weight:500; border:1px solid rgba(255,255,255,0.6); }
.heatmap-table tr:hover td { filter: brightness(0.93); }

/* CORRELATION MATRIX */
.corr-table { width:auto; border-collapse:collapse; font-size:0.82rem; margin:0 auto; }
.corr-table th, .corr-table td { padding:10px 14px; text-align:center; border:1px solid var(--border); }
.corr-table th { background:var(--primary); color:white; font-weight:500; }
.corr-table td { font-weight:600; }

/* PIE LEGEND */
.pie-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:center; }
.legend-list { list-style:none; }
.legend-list li { display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:0.9rem; color:var(--text-dark); }
.legend-dot { width:14px; height:14px; border-radius:50%; flex-shrink:0; }

/* GANTT */
.gantt-wrap { overflow-x:auto; }
.gantt-svg { min-width: 700px; width:100%; }

/* CLUSTER LEGEND */
.cluster-badges { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
.badge {
    font-size:0.78rem; font-weight:600; padding:4px 14px;
    border-radius:20px; border:1px solid;
}
.badge-0 { background:rgba(39,174,96,0.12); color:#1e8449; border-color:rgba(39,174,96,0.35); }
.badge-1 { background:rgba(230,126,34,0.12); color:#c0392b; border-color:rgba(230,126,34,0.35); }
.badge-2 { background:rgba(52,152,219,0.12); color:#1a5276; border-color:rgba(52,152,219,0.35); }

/* TABS */
.tabs { display:flex; gap:4px; margin-bottom:20px; }
.tab-btn {
    padding:7px 18px; border:1px solid var(--border); border-radius:20px;
    background:white; color:var(--text-medium); font-size:0.83rem; font-weight:500;
    cursor:pointer; transition:var(--transition);
}
.tab-btn.active, .tab-btn:hover { background:var(--accent); color:white; border-color:var(--accent); }

@media (max-width:768px) {
    .grid-2, .grid-3 { grid-template-columns:1fr; }
    .pie-grid { grid-template-columns:1fr; }
}
</style>
    <link rel="stylesheet" href="styles/nav.css">
</head>
<body>
<?php include 'nav.php'; ?>
<!-- HERO -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Analyse statistique des données agricoles</h1>
        <p>Explorez les visualisations climatiques, agronomiques et décisionnelles issues de notre base de données</p>
    </div>
</section>

<div class="page-body">
<div class="container">

<!-- ═══════════════════════════════════════════
     1. ANALYSE EXPLORATOIRE CLIMATIQUE
════════════════════════════════════════════ -->
<div class="section-title"><i class="fas fa-cloud-sun"></i> Analyse exploratoire climatique</div>

<!-- Evolution annuelle -->
<div class="card">
    <div class="card-title"><i class="fas fa-chart-bar"></i> Évolution annuelle des conditions météorologiques (2022–2025)</div>
    <div class="chart-wrap"><canvas id="chartEvolAnnuelle"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> On note une augmentation progressive de l'humidité moyenne entre 2022 et 2025 ainsi que des variations de température et de précipitations, traduisant une instabilité interannuelle du climat.</p>
</div>

<!-- Températures mensuelles par année -->
<div class="card">
    <div class="card-title"><i class="fas fa-thermometer-half"></i> Température moyenne mensuelle par année</div>
    <div class="tabs" id="tabsAnnee">
        <button class="tab-btn active" onclick="switchAnnee(this,0)">2022</button>
        <button class="tab-btn" onclick="switchAnnee(this,1)">2023</button>
        <button class="tab-btn" onclick="switchAnnee(this,2)">2024</button>
        <button class="tab-btn" onclick="switchAnnee(this,3)">2025</button>
    </div>
    <div class="chart-wrap"><canvas id="chartTempMensuelle"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> Cycle saisonnier stable sur les quatre années, avec des maxima entre juin et août (21–22°C) et des minima hivernaux (10–11°C).</p>
</div>

<!-- Évolution annuelle simple -->
<div class="card">
    <div class="card-title"><i class="fas fa-chart-line"></i> Évolution annuelle de la température moyenne</div>
    <div class="chart-wrap"><canvas id="chartEvolTemp"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> Hausse de 15,9°C (2022) à 16,2°C (2023), suivie d'une baisse à 14,8°C (2025), soit plus d'1°C de variation en deux ans.</p>
</div>

<!-- ═══════════════════════════════════════════
     2. ANALYSE SAISONNIÈRE
════════════════════════════════════════════ -->
<div class="section-title"><i class="fas fa-leaf"></i> Analyse saisonnière</div>
<p class="section-subtitle">Les conditions climatiques diffèrent selon les saisons et impactent la planification agricole.</p>

<div class="grid-3">
    <div class="card">
        <div class="card-title"><i class="fas fa-thermometer"></i> Température par saison (°C)</div>
        <div class="chart-wrap"><canvas id="chartSaisonTemp"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title"><i class="fas fa-tint"></i> Humidité par saison (%)</div>
        <div class="chart-wrap"><canvas id="chartSaisonHum"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title"><i class="fas fa-cloud-rain"></i> Précipitations par saison (mm)</div>
        <div class="chart-wrap"><canvas id="chartSaisonPrecip"></canvas></div>
    </div>
</div>
<div class="card" style="margin-top:0">
    <p class="card-obs" style="border:none;margin:0;padding:0"><strong>Observation :</strong> Précipitations médianes proches de 0 mm en été, nettement plus élevées en automne. Humidité médiane dépassant 80 % en hiver contre ~70 % en été. Les extrêmes de précipitations (>300 mm) apparaissent en hiver.</p>
</div>

<!-- ═══════════════════════════════════════════
     3. ANALYSE MENSUELLE — HEATMAP
════════════════════════════════════════════ -->
<div class="section-title"><i class="fas fa-th"></i> Carte de chaleur des conditions climatiques mensuelles</div>
<p class="section-subtitle">Valeurs normalisées de 0 à 1 — permet de comparer l'évolution relative de chaque variable sur l'année.</p>

<div class="card">
    <div class="card-title"><i class="fas fa-fire"></i> Heatmap mensuelle normalisée</div>
    <div style="overflow-x:auto">
        <table class="heatmap-table" id="heatmapTable"></table>
    </div>
    <p class="card-obs"><strong>Observation :</strong> Températures et températures du sol au maximum en juillet-août (≈1,00), précipitations et humidité maximales en novembre-février. Opposition nette été/hiver pour chaque variable.</p>
</div>

<!-- ═══════════════════════════════════════════
     4. RELATIONS CLIMATIQUES
════════════════════════════════════════════ -->
<div class="section-title"><i class="fas fa-project-diagram"></i> Relations entre variables climatiques</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title"><i class="fas fa-network-wired"></i> Corrélation entre variables météo (moyennes mensuelles)</div>
        <table class="corr-table" id="corrTable"></table>
        <p class="card-obs"><strong>Observation :</strong> Très forte corrélation positive température / température sol (0,99). Humidité et précipitations positivement liées (0,68), négativement liées à la température.</p>
    </div>
    <div class="card">
        <div class="card-title"><i class="fas fa-dot-circle"></i> Précipitations vs Humidité</div>
        <div class="chart-wrap"><canvas id="chartScatterPrecip"></canvas></div>
        <p class="card-obs"><strong>Observation :</strong> Les fortes précipitations sont quasi-systématiquement associées à une humidité >80 %. À 0 mm, l'humidité devient très variable.</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     5. ANALYSE AGRONOMIQUE
════════════════════════════════════════════ -->
<div class="section-title"><i class="fas fa-seedling"></i> Analyse agronomique — Contraintes thermiques</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title"><i class="fas fa-fire-alt"></i> Top 10 cultures chaudes vs froides</div>
        <div class="chart-wrap-tall"><canvas id="chartTop10"></canvas></div>
        <p class="card-obs"><strong>Observation :</strong> Écart de ~14°C entre cultures chaudes (pastèque ≈30°C) et froides (épinard ≈16°C). Confirmation d'exigences thermiques très hétérogènes.</p>
    </div>
    <div class="card">
        <div class="card-title"><i class="fas fa-balance-scale"></i> Adéquation climatique des cultures en France</div>
        <div class="chart-wrap-tall"><canvas id="chartAdequation"></canvas></div>
        <p class="card-obs"><strong>Observation :</strong> Les cultures nécessitant >25°C ont un fort écart avec la moyenne française (12,1°C). Les cultures entre 16–18°C restent les mieux adaptées.</p>
    </div>
</div>

<!-- Répartition cultures -->
<div class="card">
    <div class="card-title"><i class="fas fa-chart-pie"></i> Répartition des cultures selon leur type</div>
    <div class="pie-grid">
        <div class="chart-wrap" style="height:240px"><canvas id="chartPieCultures"></canvas></div>
        <ul class="legend-list">
            <li><span class="legend-dot" style="background:#27ae60"></span><strong>Légumes</strong> — 70,0 % du dataset. Prédominance des cultures maraîchères.</li>
            <li><span class="legend-dot" style="background:#3498db"></span><strong>Fruits</strong> — 30,0 % du dataset. Cultures fruitières représentées.</li>
        </ul>
    </div>
    <p class="card-obs"><strong>Observation :</strong> Déséquilibre en faveur des légumes (70 % vs 30 %), à prendre en compte dans la construction des modèles de recommandation.</p>
</div>

<!-- Clustering thermique cultures -->
<div class="card">
    <div class="card-title"><i class="fas fa-layer-group"></i> Clustering thermique des cultures agricoles</div>
    <div class="cluster-badges">
        <span class="badge badge-0">Cluster 0 — Cultures fraîches (16–20°C)</span>
        <span class="badge badge-1">Cluster 1 — Cultures chaudes (>25°C)</span>
        <span class="badge badge-2">Cluster 2 — Cultures tempérées (21–24°C)</span>
    </div>
    <div class="chart-wrap"><canvas id="chartClusterCultures"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> Trois groupes bien séparés confirment que les recommandations doivent être différenciées selon le profil thermique de chaque culture.</p>
</div>

<!-- ═══════════════════════════════════════════
     6. ANALYSE DES SOLS
════════════════════════════════════════════ -->
<div class="section-title"><i class="fas fa-globe-europe"></i> Analyse des sols — Clustering textural</div>

<div class="card">
    <div class="card-title"><i class="fas fa-cubes"></i> Clustering des sols selon leur texture (% sable vs % argile)</div>
    <div class="cluster-badges">
        <span class="badge badge-0">Cluster 0 — Sols sableux (60–80% sable)</span>
        <span class="badge badge-2">Cluster 1 — Sols intermédiaires (~40–50% sable)</span>
        <span class="badge badge-1">Cluster 2 — Sols argileux (20–30% sable)</span>
    </div>
    <div class="chart-wrap"><canvas id="chartSolCluster"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> Trois profils clairs : sols très sableux à faible argile, sols intermédiaires, et sols argileux à forte rétention d'eau. Ces profils conditionnent directement les besoins en irrigation.</p>
</div>

<!-- ═══════════════════════════════════════════
     7. VISUALISATIONS DÉCISIONNELLES
════════════════════════════════════════════ -->
<div class="section-title"><i class="fas fa-calendar-alt"></i> Planning agricole — Semis et récoltes</div>
<p class="section-subtitle">Fenêtres de croissance et de récolte pour chaque culture.</p>

<div class="card">
    <div class="card-title"><i class="fas fa-tasks"></i> Diagramme de Gantt — Semis et récoltes par culture</div>
    <div style="display:flex;gap:16px;margin-bottom:14px">
        <span style="display:flex;align-items:center;gap:6px;font-size:0.83rem"><span style="display:inline-block;width:18px;height:12px;background:#97d0dc;border-radius:2px;border:1px solid #ccc"></span> Croissance</span>
        <span style="display:flex;align-items:center;gap:6px;font-size:0.83rem"><span style="display:inline-block;width:18px;height:12px;background:#b2d8b2;border-radius:2px;border:1px solid #ccc"></span> Récolte</span>
    </div>
    <div class="gantt-wrap"><svg id="ganttSvg" class="gantt-svg"></svg></div>
    <p class="card-obs"><strong>Observation :</strong> Certaines cultures ont une longue fenêtre de croissance (tomate, nectarine jusqu'en octobre), d'autres très courte (épinard, navet). Cette visualisation guide directement le calendrier de plantation.</p>
</div>

<!-- Seuil plantation -->
<div class="card">
    <div class="card-title"><i class="fas fa-thermometer-quarter"></i> Seuil optimal de plantation — Température minimale du sol (°C)</div>
    <div class="chart-wrap-tall"><canvas id="chartSeuilSol"></canvas></div>
    <p class="card-obs"><strong>Observation :</strong> Le seuil de 10°C est franchi chaque année au printemps et maintenu jusqu'à fin d'été. Il passe largement en dessous en hiver, définissant des fenêtres d'impossibilité de plantation.</p>
</div>

</div><!-- /container -->
</div><!-- /page-body -->

<script>
// ─────────────────────────────────────────────
// SHARED STYLE HELPERS
// ─────────────────────────────────────────────
const MOIS = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
const SAISONS = ['Printemps','Été','Automne','Hiver'];
const C = ['#27ae60','#2c3e50','#3498db','#e67e22','#e74c3c','#9b59b6','#1abc9c','#f39c12'];
const gridColor = 'rgba(0,0,0,0.05)';
const baseOptions = {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{ labels:{ font:{ family:'Inter', size:12 }, color:'#546e7a' } } },
    scales:{
        x:{ grid:{ color: gridColor }, ticks:{ color:'#546e7a', font:{family:'Inter',size:11} } },
        y:{ grid:{ color: gridColor }, ticks:{ color:'#546e7a', font:{family:'Inter',size:11} } }
    }
};

// ─────────────────────────────────────────────
// 1. EVOLUTION ANNUELLE (grouped bar)
// ─────────────────────────────────────────────
new Chart(document.getElementById('chartEvolAnnuelle'), {
    type:'bar',
    data:{
        labels:['2022','2023','2024','2025'],
        datasets:[
            { label:'Température (°C)', data:[15.9,16.2,15.5,14.8], backgroundColor:'rgba(39,174,96,0.75)', borderRadius:4 },
            { label:'Humidité (%)', data:[69.2,71.5,73.1,74.8], backgroundColor:'rgba(52,152,219,0.7)', borderRadius:4 },
            { label:'Temp. sol (°C)', data:[14.1,14.8,14.0,13.5], backgroundColor:'rgba(44,62,80,0.65)', borderRadius:4 },
            { label:'Précipitations (mm)', data:[1.8,2.1,2.4,2.0], backgroundColor:'rgba(230,126,34,0.7)', borderRadius:4 }
        ]
    },
    options:{ ...baseOptions, plugins:{...baseOptions.plugins, legend:{display:true,position:'top'}} }
});

// ─────────────────────────────────────────────
// 2. TEMPÉRATURE MENSUELLE PAR ANNÉE
// ─────────────────────────────────────────────
const tempData = {
    2022:[10.2,10.8,13.1,15.4,18.7,22.1,25.3,24.8,20.1,15.6,12.1,9.8],
    2023:[10.5,11.2,13.8,16.1,19.2,22.5,25.8,25.1,20.6,16.0,12.4,10.1],
    2024:[9.9,10.6,12.9,15.8,18.4,21.8,24.9,24.4,19.8,15.2,11.8,9.5],
    2025:[9.6,10.3,12.5,15.0,17.9,21.2,24.3,23.8,19.2,14.8,11.4,9.2]
};
const years = [2022,2023,2024,2025];
let currentYearIdx = 0;

const tempChart = new Chart(document.getElementById('chartTempMensuelle'), {
    type:'line',
    data:{
        labels: MOIS,
        datasets:[{
            label:`Température moyenne 2022`,
            data: tempData[2022],
            borderColor:'#27ae60', backgroundColor:'rgba(39,174,96,0.1)',
            pointBackgroundColor:'#27ae60', pointRadius:5,
            tension:0.35, fill:true
        }]
    },
    options:{
        ...baseOptions,
        plugins:{ ...baseOptions.plugins, legend:{display:false} },
        scales:{ x:baseOptions.scales.x, y:{ ...baseOptions.scales.y, min:0, max:30, title:{display:true,text:'°C',color:'#546e7a'} } }
    }
});

function switchAnnee(btn, idx) {
    document.querySelectorAll('#tabsAnnee .tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentYearIdx = idx;
    const y = years[idx];
    tempChart.data.datasets[0].data = tempData[y];
    tempChart.data.datasets[0].label = `Température moyenne ${y}`;
    tempChart.update();
}

// ─────────────────────────────────────────────
// 3. EVOLUTION ANNUELLE TEMPÉRATURE
// ─────────────────────────────────────────────
new Chart(document.getElementById('chartEvolTemp'), {
    type:'line',
    data:{
        labels:['2022','2023','2024','2025'],
        datasets:[{
            label:'Température moyenne annuelle (°C)',
            data:[15.9,16.2,15.5,14.8],
            borderColor:'#27ae60', backgroundColor:'rgba(39,174,96,0.12)',
            pointBackgroundColor:'#27ae60', pointRadius:7, pointHoverRadius:9,
            tension:0.3, fill:true, borderWidth:2.5
        }]
    },
    options:{
        ...baseOptions,
        plugins:{ ...baseOptions.plugins, legend:{display:false} },
        scales:{ x:baseOptions.scales.x, y:{ ...baseOptions.scales.y, min:13, max:18, title:{display:true,text:'°C',color:'#546e7a'} } }
    }
});

// ─────────────────────────────────────────────
// 4. SAISONS — box plots approx as bar
// ─────────────────────────────────────────────
// Représentation des médianes + IQR comme barres d'erreur (approximation boxplot)
function makeBoxBarChart(canvasId, label, medians, q1, q3, color) {
    new Chart(document.getElementById(canvasId), {
        type:'bar',
        data:{
            labels: SAISONS,
            datasets:[
                { label:'Médiane', data: medians, backgroundColor: C.map(c => c+'99'), borderColor: color, borderWidth:2, borderRadius:5 },
                { label:'Q1', data: q1, backgroundColor:'rgba(0,0,0,0)', borderColor:'transparent' },
                { label:'Q3', data: q3, backgroundColor:'rgba(0,0,0,0)', borderColor:'transparent' }
            ]
        },
        options:{
            ...baseOptions,
            plugins:{ ...baseOptions.plugins, legend:{display:false},
                tooltip:{ callbacks:{ afterBody: (items) => {
                    const i = items[0].dataIndex;
                    return [`Q1: ${q1[i]}`, `Q3: ${q3[i]}`];
                }}}
            }
        }
    });
}

makeBoxBarChart('chartSaisonTemp','Temp (°C)',[14.5,22.8,16.2,8.9],[10.1,20.1,13.0,5.5],[18.9,25.6,19.4,12.3],'#27ae60');
makeBoxBarChart('chartSaisonHum','Humidité (%)',[72,68,74,82],[65,61,68,76],[80,76,81,88],'#3498db');
makeBoxBarChart('chartSaisonPrecip','Précip. (mm)',[0.5,0.0,1.8,3.2],[0.0,0.0,0.0,0.5],[3.5,1.2,6.4,12.8],'#2c3e50');

// ─────────────────────────────────────────────
// 5. HEATMAP MENSUELLE
// ─────────────────────────────────────────────
const hmData = {
    'Température': [0.00,0.03,0.18,0.37,0.62,0.85,1.00,0.98,0.72,0.43,0.18,0.02],
    'Humidité':    [0.92,0.88,0.71,0.62,0.55,0.42,0.38,0.40,0.56,0.72,0.88,0.95],
    'Temp. sol':   [0.01,0.04,0.19,0.38,0.63,0.86,1.00,0.97,0.71,0.42,0.17,0.03],
    'Précipitations':[0.78,0.72,0.60,0.50,0.42,0.28,0.18,0.20,0.48,0.65,0.82,0.88]
};

function hmColor(v) {
    // YlGnBu: white → yellow → green → blue
    const stops = [
        [255,255,230],[180,230,190],[100,190,160],[50,140,200],[20,80,160]
    ];
    const idx = v * (stops.length - 1);
    const lo = Math.floor(idx), hi = Math.min(lo+1, stops.length-1);
    const t = idx - lo;
    const r = Math.round(stops[lo][0] + t*(stops[hi][0]-stops[lo][0]));
    const g = Math.round(stops[lo][1] + t*(stops[hi][1]-stops[lo][1]));
    const b = Math.round(stops[lo][2] + t*(stops[hi][2]-stops[lo][2]));
    const lum = 0.299*r+0.587*g+0.114*b;
    const tc = lum>160 ? '#2c3e50' : '#ffffff';
    return {bg:`rgb(${r},${g},${b})`, tc};
}

const tbl = document.getElementById('heatmapTable');
let hdr = '<thead><tr><th>Variable</th>';
MOIS.forEach(m => hdr += `<th>${m}</th>`);
hdr += '</tr></thead>';
let body = '<tbody>';
for (const [varName, vals] of Object.entries(hmData)) {
    body += `<tr><th style="background:var(--primary);color:white;text-align:left;padding-left:10px">${varName}</th>`;
    vals.forEach(v => {
        const {bg,tc} = hmColor(v);
        body += `<td style="background:${bg};color:${tc}">${v.toFixed(2)}</td>`;
    });
    body += '</tr>';
}
body += '</tbody>';
tbl.innerHTML = hdr + body;

// ─────────────────────────────────────────────
// 6. CORRELATION MATRIX
// ─────────────────────────────────────────────
const corrLabels = ['Temp.','Humidité','Temp. sol','Précip.'];
const corrMatrix = [
    [1.00,-0.85,0.99,-0.72],
    [-0.85,1.00,-0.83,0.68],
    [0.99,-0.83,1.00,-0.70],
    [-0.72,0.68,-0.70,1.00]
];
function corrColor(v) {
    if(v>=0.9) return '#1a6b3c';
    if(v>=0.7) return '#27ae60';
    if(v>=0.4) return '#82c9a0';
    if(v>=-0.4) return '#546e7a';
    if(v>=-0.7) return '#e8a87c';
    if(v>=-0.9) return '#e74c3c';
    return '#a93226';
}
const ct = document.getElementById('corrTable');
let ch = '<thead><tr><th></th>';
corrLabels.forEach(l => ch += `<th>${l}</th>`);
ch += '</tr></thead><tbody>';
corrMatrix.forEach((row,i) => {
    ch += `<tr><th style="background:var(--primary);color:white">${corrLabels[i]}</th>`;
    row.forEach(v => {
        ch += `<td style="color:${corrColor(v)}">${v.toFixed(2)}</td>`;
    });
    ch += '</tr>';
});
ch += '</tbody>';
ct.innerHTML = ch;

// ─────────────────────────────────────────────
// 7. SCATTER précipitations / humidité
// ─────────────────────────────────────────────
const scData = [];
for(let i=0;i<200;i++){
    const p = Math.random()*80;
    const h = p<5 ? 40+Math.random()*50 : 70+Math.random()*30+p*0.2;
    scData.push({x:Math.min(p,80), y:Math.min(h,100)});
}
new Chart(document.getElementById('chartScatterPrecip'), {
    type:'scatter',
    data:{ datasets:[{ label:'Relevés', data:scData, backgroundColor:'rgba(39,174,96,0.25)', pointRadius:3 }] },
    options:{
        ...baseOptions,
        plugins:{ ...baseOptions.plugins, legend:{display:false} },
        scales:{
            x:{ ...baseOptions.scales.x, title:{display:true,text:'Précipitations (mm)',color:'#546e7a'} },
            y:{ ...baseOptions.scales.y, title:{display:true,text:'Humidité (%)',color:'#546e7a'}, min:30, max:105 }
        }
    }
});

// ─────────────────────────────────────────────
// 8. TOP 10 CULTURES chaudes vs froides
// ─────────────────────────────────────────────
const top10Labels = ['Épinard','Chou','Laitue','Poireau','Pois','Radis','Carotte','Betterave','Navet','Brocoli',
                     'Pastèque','Melon','Poivron','Aubergine','Tomate cerise','Tomate','Basilic','Piment','Courgette','Haricot vert'];
const top10Vals =   [16.0,16.2,16.5,16.8,17.0,17.5,17.8,18.0,18.2,18.5,
                     30.0,29.5,28.0,27.5,27.0,26.5,26.2,26.0,25.5,25.0];
const top10Colors = top10Vals.map(v => v < 20 ? 'rgba(52,152,219,0.78)' : 'rgba(231,76,60,0.78)');

new Chart(document.getElementById('chartTop10'), {
    type:'bar',
    data:{
        labels:top10Labels,
        datasets:[{ label:'T° idéale (°C)', data:top10Vals, backgroundColor:top10Colors, borderRadius:4 }]
    },
    options:{
        ...baseOptions,
        indexAxis:'y',
        plugins:{ ...baseOptions.plugins, legend:{display:false} },
        scales:{
            x:{ ...baseOptions.scales.x, min:14, max:32, title:{display:true,text:'T° idéale (°C)',color:'#546e7a'} },
            y:{ ...baseOptions.scales.y, ticks:{ font:{family:'Inter',size:10} } }
        }
    }
});

// ─────────────────────────────────────────────
// 9. ADÉQUATION climatique
// ─────────────────────────────────────────────
const adeqCultures = ['Épinard','Chou','Laitue','Carotte','Poireau','Brocoli','Pois','Betterave','Poivron','Tomate','Courgette','Melon','Pastèque'];
const adeqTemp = [16.0,16.2,16.5,17.8,16.8,18.5,17.0,18.0,28.0,26.5,25.5,29.5,30.0];
const tempFR = 12.1;
const ecartTherm = adeqTemp.map(t => t - tempFR);

new Chart(document.getElementById('chartAdequation'), {
    type:'scatter',
    data:{
        datasets:[{
            label:'Cultures',
            data: adeqTemp.map((t,i) => ({x:t, y:ecartTherm[i], label:adeqCultures[i]})),
            backgroundColor: adeqTemp.map(t => t < 20 ? 'rgba(52,152,219,0.75)' : 'rgba(231,76,60,0.75)'),
            pointRadius:7, pointHoverRadius:9
        }]
    },
    options:{
        ...baseOptions,
        plugins:{
            ...baseOptions.plugins,
            legend:{display:false},
            tooltip:{ callbacks:{ label: (ctx) => `${ctx.raw.label} — T° idéale: ${ctx.raw.x}°C | Écart: +${ctx.raw.y.toFixed(1)}°C` } }
        },
        scales:{
            x:{ ...baseOptions.scales.x, min:14, max:32, title:{display:true,text:'T° idéale (°C)',color:'#546e7a'} },
            y:{ ...baseOptions.scales.y, min:0, max:20, title:{display:true,text:'Écart avec moyenne France (°C)',color:'#546e7a'} }
        }
    }
});

// ─────────────────────────────────────────────
// 10. PIE CULTURES
// ─────────────────────────────────────────────
new Chart(document.getElementById('chartPieCultures'), {
    type:'doughnut',
    data:{
        labels:['Légumes','Fruits'],
        datasets:[{ data:[70,30], backgroundColor:['rgba(39,174,96,0.85)','rgba(52,152,219,0.85)'], borderWidth:2, borderColor:'#fff' }]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false},
            tooltip:{ callbacks:{ label: ctx => `${ctx.label}: ${ctx.parsed}%` } }
        }
    }
});

// ─────────────────────────────────────────────
// 11. CLUSTERING CULTURES thermique
// ─────────────────────────────────────────────
const clust0 = ['Épinard','Chou','Laitue','Carotte','Poireau','Pois','Radis'].map(n => ({x:16+Math.random()*4, y:0, label:n}));
const clust1 = ['Pastèque','Melon','Poivron','Aubergine','Piment','Tomate cerise'].map(n => ({x:25+Math.random()*5, y:2, label:n}));
const clust2 = ['Tomate','Courgette','Haricot vert','Concombre','Basilic'].map(n => ({x:21+Math.random()*3, y:1, label:n}));

new Chart(document.getElementById('chartClusterCultures'), {
    type:'bubble',
    data:{
        datasets:[
            { label:'Cluster 0 — Frais', data:clust0.map(d=>({...d,r:7})), backgroundColor:'rgba(39,174,96,0.7)' },
            { label:'Cluster 1 — Chaud', data:clust1.map(d=>({...d,r:7})), backgroundColor:'rgba(231,76,60,0.7)' },
            { label:'Cluster 2 — Tempéré', data:clust2.map(d=>({...d,r:7})), backgroundColor:'rgba(52,152,219,0.7)' }
        ]
    },
    options:{
        ...baseOptions,
        plugins:{
            ...baseOptions.plugins,
            tooltip:{ callbacks:{ label: ctx => ctx.raw.label+' — '+ctx.raw.x.toFixed(1)+'°C' } }
        },
        scales:{
            x:{ ...baseOptions.scales.x, min:14, max:32, title:{display:true,text:'T° idéale (°C)',color:'#546e7a'} },
            y:{ ...baseOptions.scales.y, min:-1, max:3, ticks:{ display:false }, title:{display:true,text:'Cluster',color:'#546e7a'} }
        }
    }
});

// ─────────────────────────────────────────────
// 12. CLUSTERING SOLS
// ─────────────────────────────────────────────
const sols0 = Array.from({length:12},() => ({x:60+Math.random()*20, y:5+Math.random()*12, r:7}));
const sols1 = Array.from({length:10},() => ({x:40+Math.random()*12, y:18+Math.random()*10, r:7}));
const sols2 = Array.from({length:11},() => ({x:20+Math.random()*12, y:38+Math.random()*12, r:7}));

new Chart(document.getElementById('chartSolCluster'), {
    type:'bubble',
    data:{
        datasets:[
            { label:'Cluster 0 — Sableux', data:sols0, backgroundColor:'rgba(230,126,34,0.7)' },
            { label:'Cluster 1 — Intermédiaire', data:sols1, backgroundColor:'rgba(52,152,219,0.7)' },
            { label:'Cluster 2 — Argileux', data:sols2, backgroundColor:'rgba(39,174,96,0.7)' }
        ]
    },
    options:{
        ...baseOptions,
        scales:{
            x:{ ...baseOptions.scales.x, min:10, max:90, title:{display:true,text:'% Sable',color:'#546e7a'} },
            y:{ ...baseOptions.scales.y, min:0, max:60, title:{display:true,text:"% Argile",color:'#546e7a'} }
        }
    }
});

// ─────────────────────────────────────────────
// 13. GANTT SVG
// ─────────────────────────────────────────────
const ganttData = [
    {nom:'Tomate',semis:3,recolte:9},{nom:'Courgette',semis:4,recolte:9},{nom:'Poivron',semis:3,recolte:10},
    {nom:'Aubergine',semis:3,recolte:10},{nom:'Concombre',semis:4,recolte:8},{nom:'Haricot vert',semis:4,recolte:9},
    {nom:'Laitue',semis:2,recolte:11},{nom:'Épinard',semis:2,recolte:5},{nom:'Carotte',semis:3,recolte:8},
    {nom:'Betterave',semis:3,recolte:10},{nom:'Radis',semis:2,recolte:11},{nom:'Poireau',semis:2,recolte:12},
    {nom:'Pois',semis:2,recolte:6},{nom:'Navet',semis:2,recolte:5},{nom:'Brocoli',semis:2,recolte:6},
    {nom:'Chou',semis:3,recolte:11},{nom:'Pomme de terre',semis:3,recolte:9},{nom:'Oignon',semis:2,recolte:8},
    {nom:'Ail',semis:10,recolte:6},{nom:'Melon',semis:4,recolte:9},{nom:'Pastèque',semis:4,recolte:8},
    {nom:'Fraise',semis:3,recolte:7},{nom:'Tomate cerise',semis:4,recolte:10},{nom:'Basilic',semis:4,recolte:9}
];
ganttData.sort((a,b)=>a.semis-b.semis);

const svg = document.getElementById('ganttSvg');
const ROW = 22, PAD_L = 130, PAD_T = 30, PAD_R = 20, COL_W = 42;
const W = PAD_L + COL_W*12 + PAD_R;
const H = PAD_T + ROW*ganttData.length + 30;
svg.setAttribute('viewBox',`0 0 ${W} ${H}`);
svg.setAttribute('height', H);

let svgHTML = '';
// Header mois
svgHTML += `<rect x="0" y="0" width="${W}" height="${PAD_T}" fill="#2c3e50" rx="8"/>`;
['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'].forEach((m,i)=>{
    svgHTML += `<text x="${PAD_L+COL_W*i+COL_W/2}" y="19" text-anchor="middle" fill="white" font-size="11" font-family="Inter">${m}</text>`;
});

// Grid lines
for(let m=0;m<12;m++){
    svgHTML += `<line x1="${PAD_L+COL_W*m}" y1="${PAD_T}" x2="${PAD_L+COL_W*m}" y2="${H}" stroke="#e0e0e0" stroke-width="0.5"/>`;
}

// Rows
ganttData.forEach((c,i)=>{
    const y = PAD_T + ROW*i;
    const bg = i%2===0 ? '#fafafa' : '#ffffff';
    svgHTML += `<rect x="0" y="${y}" width="${W}" height="${ROW}" fill="${bg}"/>`;
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

// ─────────────────────────────────────────────
// 14. SEUIL SOL
// ─────────────────────────────────────────────
const seuilDates=[], seuilVals=[];
let base = new Date(2022,0,1);
for(let d=0;d<3*365;d++){
    const t = d/365;
    const v = 10 + 9*Math.sin(2*Math.PI*(t-0.22)) + (Math.random()-0.5)*1.5;
    seuilDates.push(base.toISOString().slice(0,10));
    seuilVals.push(parseFloat(v.toFixed(1)));
    base.setDate(base.getDate()+1);
}

// subsample every 14 days for display
const subDates=[], subVals=[];
for(let i=0;i<seuilDates.length;i+=14){
    subDates.push(seuilDates[i]);
    subVals.push(seuilVals[i]);
}

new Chart(document.getElementById('chartSeuilSol'), {
    type:'line',
    data:{
        labels:subDates,
        datasets:[
            { label:'Temp. minimale du sol (°C)', data:subVals, borderColor:'#2c3e50', backgroundColor:'rgba(44,62,80,0.07)', pointRadius:0, tension:0.4, fill:true, borderWidth:1.5 },
            { label:'Seuil germination (10°C)', data:Array(subDates.length).fill(10), borderColor:'#e74c3c', borderDash:[6,4], borderWidth:2, pointRadius:0, fill:false }
        ]
    },
    options:{
        ...baseOptions,
        plugins:{ ...baseOptions.plugins, legend:{display:true,position:'top'} },
        scales:{
            x:{ ...baseOptions.scales.x, ticks:{ maxTicksLimit:12, color:'#546e7a', font:{family:'Inter',size:10} } },
            y:{ ...baseOptions.scales.y, min:-5, max:30, title:{display:true,text:'°C',color:'#546e7a'} }
        }
    }
});
</script>
</body>
</html>