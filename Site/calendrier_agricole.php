<?php
$cp = $_GET['cp'] ?? '';
$cp = trim($cp);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning Agricole - Algorithmes ML</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/nav.css">
    <link rel="stylesheet" href="styles/algo.css">
    <link rel="stylesheet" href="styles/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f4fff6, #eef7ff);
            color: #1f2937;
        }

        .page {
            max-width: 1350px;
            margin: 30px auto;
            padding: 20px;
        }

        .card {
            background: rgba(255,255,255,0.96);
            border-radius: 22px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.08);
            padding: 25px;
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 32px;
            color: #14532d;
        }

        .subtitle {
            margin-top: 0;
            color: #4b5563;
        }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .form-inline {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        input[type="text"] {
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 15px;
            min-width: 180px;
        }

        button {
            padding: 12px 18px;
            border: none;
            border-radius: 12px;
            background: #16a34a;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
        }

        button:hover {
            background: #15803d;
        }

        .info-box {
            margin-bottom: 18px;
            padding: 15px 18px;
            border-radius: 14px;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #166534;
            font-size: 15px;
        }

        .period-box {
            margin-bottom: 18px;
            padding: 14px 18px;
            border-radius: 14px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 15px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-item {
            background: #f9fafb;
            border-radius: 16px;
            padding: 16px;
            border: 1px solid #e5e7eb;
        }

        .summary-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .summary-value {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        #calendar {
            margin-top: 20px;
            min-height: 700px;
            background: white;
            border-radius: 14px;
            padding: 10px;
        }

        .legend {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 20px;
            font-size: 14px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f9fafb;
            padding: 8px 12px;
            border-radius: 10px;
        }

        .dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-green { background: #16a34a; }
        .dot-red { background: #dc2626; }
        .dot-blue { background: #2563eb; }

        .section-title {
            margin-top: 28px;
            margin-bottom: 14px;
            font-size: 22px;
            color: #14532d;
        }

        #recommendedList {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 12px;
        }

        .culture-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 14px;
        }

        .culture-title {
            font-size: 16px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 6px;
        }

        .culture-meta {
            font-size: 13px;
            color: #4b5563;
            line-height: 1.5;
        }

        .empty-state {
            margin-top: 12px;
            padding: 18px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 16px;
            color: #991b1b;
        }

        #loadingBox {
            display: none;
            margin: 14px 0;
            padding: 14px 18px;
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-weight: 600;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal {
            width: min(560px, 100%);
            background: white;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            background: #f0fdf4;
            border-bottom: 1px solid #dcfce7;
        }

        .modal-header h3 {
            margin: 0;
            color: #14532d;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #374151;
            padding: 0;
        }

        .modal-body {
            padding: 20px;
            white-space: pre-line;
            line-height: 1.6;
            color: #374151;
        }

        .fc .fc-toolbar-title {
            color: #14532d;
            font-size: 24px !important;
        }

        .fc-event {
            border: none !important;
            border-radius: 10px !important;
            padding: 2px 4px !important;
            font-size: 13px !important;
        }

        @media (max-width: 900px) {
            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
        #titre_hero{
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
     <section class="hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1 id="titre_hero">Prédiction de la temperature</h1>
                <p>Prédisez ci-dessous la temperature sur le court, moyen ou long terme</p>
            </div>
        </section>
        <section class="presentation-section">
<div class="page">
    <div class="card">
        <div class="topbar">
            <div>
                <h1>🌱 Calendrier agricole intelligent</h1>
                <p class="subtitle">Choisis un code postal pour voir les cultures recommandées selon la prédiction météo.</p>
            </div>

            <form class="form-inline" method="GET" action="">
                <input
                    type="text"
                    name="cp"
                    placeholder="Ex : 34000"
                    value="<?= htmlspecialchars($cp) ?>"
                    required
                >
                <button type="submit">Afficher</button>
            </form>
        </div>

        <?php if ($cp !== ''): ?>
            <div class="info-box">
                Code postal sélectionné : <strong><?= htmlspecialchars($cp) ?></strong>
            </div>
        <?php else: ?>
            <div class="info-box">
                Entre un code postal pour charger les prédictions et les cultures recommandées.
            </div>
        <?php endif; ?>

        <div class="period-box" id="periodBox">
            Période affichée : <strong>non chargée</strong>
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Station choisie</div>
                <div class="summary-value" id="summaryStation">—</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Température prédite</div>
                <div class="summary-value" id="summaryTemp">—</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Horizon utilisé</div>
                <div class="summary-value" id="summaryHorizon">—</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Cultures recommandées</div>
                <div class="summary-value" id="summaryCount">0</div>
            </div>
        </div>

        <div id="loadingBox">Chargement des prédictions et des cultures...</div>

        <div id="calendar"></div>

        <div class="legend">
            <div class="legend-item">
                <span class="dot dot-green"></span> Cultures recommandées
            </div>
            <div class="legend-item">
                <span class="dot dot-red"></span> Aucune culture recommandée
            </div>
            <div class="legend-item">
                <span class="dot dot-blue"></span> Température prédite
            </div>
        </div>

        <h2 class="section-title">Cultures recommandées pour la période affichée</h2>
        <div id="recommendedList"></div>
        <div id="emptyState" class="empty-state" style="display:none;">
            Aucune culture recommandée pour cette période et cette température.
        </div>
    </div>
</div>

<div class="modal-overlay" id="eventModalOverlay">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Détails</h3>
            <button class="modal-close" id="modalCloseBtn">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>
</section>
 <?php include 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const cp = <?= json_encode($cp) ?>;

    const loadingBox = document.getElementById('loadingBox');
    const summaryStation = document.getElementById('summaryStation');
    const summaryTemp = document.getElementById('summaryTemp');
    const summaryHorizon = document.getElementById('summaryHorizon');
    const summaryCount = document.getElementById('summaryCount');
    const recommendedList = document.getElementById('recommendedList');
    const emptyState = document.getElementById('emptyState');
    const periodBox = document.getElementById('periodBox');

    const modalOverlay = document.getElementById('eventModalOverlay');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalCloseBtn = document.getElementById('modalCloseBtn');

    let currentHorizon = 'monthly';
    let latestDetails = null;

    function showLoading() {
        loadingBox.style.display = 'block';
    }

    function hideLoading() {
        loadingBox.style.display = 'none';
    }

    function updateSummary(meta) {
        summaryStation.textContent = meta.station || '—';
        summaryTemp.textContent = meta.temperature_predite !== null && meta.temperature_predite !== undefined
            ? `${meta.temperature_predite}°C`
            : '—';
        summaryHorizon.textContent = meta.horizon || '—';
        summaryCount.textContent = meta.cultures_count ?? 0;
    }

    function renderRecommendedList(cultures) {
        recommendedList.innerHTML = '';

        if (!cultures || cultures.length === 0) {
            emptyState.style.display = 'block';
            return;
        }

        emptyState.style.display = 'none';

        cultures.forEach(culture => {
            const card = document.createElement('div');
            card.className = 'culture-card';

            card.innerHTML = `
                <div class="culture-title">🌱 ${culture.nom_culture}</div>
                <div class="culture-meta">
                    <strong>Type :</strong> ${culture.type_culture}<br>
                    <strong>Saison :</strong> ${culture.saison}<br>
                    <strong>Remarque :</strong> ${culture.remarque ? culture.remarque : 'Aucune'}
                </div>
            `;

            recommendedList.appendChild(card);
        });
    }

    function openModal(title, details) {
        modalTitle.textContent = title || 'Détails';
        modalBody.textContent = details || 'Aucun détail disponible.';
        modalOverlay.style.display = 'flex';
    }

    function closeModal() {
        modalOverlay.style.display = 'none';
    }

    modalCloseBtn.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) closeModal();
    });

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        firstDay: 1,
        height: 'auto',

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },

        buttonText: {
            today: 'Aujourd’hui',
            month: 'Mois',
            week: 'Semaine',
            day: 'Jour',
            list: 'Liste'
        },

        datesSet: function(info) {
            if (info.view.type === 'timeGridDay') {
                currentHorizon = 'journalier';
            } else if (info.view.type === 'timeGridWeek') {
                currentHorizon = 'weekly';
            } else {
                currentHorizon = 'monthly';
            }

            const startText = info.startStr.substring(0, 10);
            const endText = info.endStr.substring(0, 10);
            periodBox.innerHTML = `Période affichée : <strong>${startText}</strong> → <strong>${endText}</strong>`;
        },

        events: function(fetchInfo, successCallback, failureCallback) {
            if (!cp) {
                updateSummary({
                    station: '—',
                    temperature_predite: null,
                    horizon: '—',
                    cultures_count: 0
                });
                renderRecommendedList([]);
                successCallback([]);
                return;
            }

            showLoading();

            const targetDate = fetchInfo.startStr.substring(0, 10);

            const url = `events_agricoles.php?cp=${encodeURIComponent(cp)}`
                + `&horizon=${encodeURIComponent(currentHorizon)}`
                + `&start=${encodeURIComponent(fetchInfo.startStr)}`
                + `&end=${encodeURIComponent(fetchInfo.endStr)}`
                + `&target_date=${encodeURIComponent(targetDate)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    hideLoading();

                    if (data.error) {
                        console.error("Erreur events_agricoles.php :", data.error);
                        updateSummary({
                            station: 'Erreur',
                            temperature_predite: null,
                            horizon: currentHorizon,
                            cultures_count: 0
                        });
                        renderRecommendedList([]);
                        successCallback([]);
                        return;
                    }

                    latestDetails = data.meta || null;

                    if (latestDetails) {
                        updateSummary({
                            station: latestDetails.station,
                            temperature_predite: latestDetails.temperature_predite,
                            horizon: latestDetails.horizon,
                            cultures_count: latestDetails.cultures_count
                        });
                        renderRecommendedList(latestDetails.cultures_recommandees || []);
                    } else {
                        updateSummary({
                            station: '—',
                            temperature_predite: null,
                            horizon: currentHorizon,
                            cultures_count: 0
                        });
                        renderRecommendedList([]);
                    }

                    successCallback(data.events || []);
                })
                .catch(error => {
                    hideLoading();
                    console.error("Erreur calendrier :", error);
                    updateSummary({
                        station: 'Erreur',
                        temperature_predite: null,
                        horizon: currentHorizon,
                        cultures_count: 0
                    });
                    renderRecommendedList([]);
                    failureCallback(error);
                });
        },

        eventClick: function(info) {
            openModal(info.event.title, info.event.extendedProps.details);
        }
    });

    calendar.render();
});
</script>
</body>
</html>