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
    <link rel="stylesheet" href="styles/calendrier.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  
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
                        <input type="text" name="cp" placeholder="Ex : 34000" value="<?= htmlspecialchars($cp) ?>" required>
                        <button type="submit">Afficher</button>
                    </form>
                </div>

                <?php if ($cp !== ''): ?>
                    <div class="info-box">Code postal sélectionné : <strong><?= htmlspecialchars($cp) ?></strong></div>
                <?php else: ?>
                    <div class="info-box">Entre un code postal pour charger les prédictions et les cultures recommandées.</div>
                <?php endif; ?>

                <div class="period-box" id="periodBox">Période affichée : <strong>non chargée</strong></div>

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
                    <div class="legend-item"><span class="dot dot-green"></span> Cultures recommandées</div>
                    <div class="legend-item"><span class="dot dot-red"></span> Aucune culture recommandée</div>
                    <div class="legend-item"><span class="dot dot-blue"></span> Température prédite</div>
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

    const loadingBox     = document.getElementById('loadingBox');
    const summaryStation = document.getElementById('summaryStation');
    const summaryTemp    = document.getElementById('summaryTemp');
    const summaryHorizon = document.getElementById('summaryHorizon');
    const summaryCount   = document.getElementById('summaryCount');
    const recommendedList = document.getElementById('recommendedList');
    const emptyState     = document.getElementById('emptyState');
    const periodBox      = document.getElementById('periodBox');
    const modalOverlay   = document.getElementById('eventModalOverlay');
    const modalTitle     = document.getElementById('modalTitle');
    const modalBody      = document.getElementById('modalBody');
    const modalCloseBtn  = document.getElementById('modalCloseBtn');

    // ✅ Variables externes au calendrier
    let currentHorizon  = 'monthly';
    let previousHorizon = null;
    let isRefetching    = false; // ✅ Garde-fou contre la boucle infinie

    function showLoading() { loadingBox.style.display = 'block'; }
    function hideLoading() { loadingBox.style.display = 'none'; }

    function updateSummary(meta) {
        summaryStation.textContent = meta.station || '—';
        summaryTemp.textContent = (meta.temperature_predite !== null && meta.temperature_predite !== undefined)
            ? `${meta.temperature_predite}°C` : '—';
        summaryHorizon.textContent = meta.horizon || '—';
    }

    function renderRecommendedList(cultures) {
        recommendedList.innerHTML = '';
        if (!cultures || cultures.length === 0) {
            emptyState.style.display = 'block';
            summaryCount.textContent = "0";
            return;
        }
        emptyState.style.display = 'none';

        const culturesUniques = [];
        const nomsVus = new Set();
        cultures.forEach(culture => {
            if (!nomsVus.has(culture.nom_culture)) {
                nomsVus.add(culture.nom_culture);
                culturesUniques.push(culture);
            }
        });

        summaryCount.textContent = culturesUniques.length;
        culturesUniques.forEach(culture => {
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
        modalBody.textContent  = details || 'Aucun détail disponible.';
        modalOverlay.style.display = 'flex';
    }
    function closeModal() { modalOverlay.style.display = 'none'; }

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
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,dayGridWeek,dayGridDay,listMonth'
        },

        buttonText: {
            today: "Aujourd'hui",
            month: 'Mois',
            week:  'Semaine',
            day:   'Jour',
            list:  'Liste'
        },

        datesSet: function(info) {
            // ✅ Mise à jour de la période affichée
            const startText = info.startStr.substring(0, 10);
            const endText   = info.endStr.substring(0, 10);
            periodBox.innerHTML = `Période affichée : <strong>${startText}</strong> → <strong>${endText}</strong>`;

            // ✅ Détection fiable via info.view.type
            const viewType = info.view.type;
            let newHorizon;
            if (viewType === 'dayGridDay') {
                newHorizon = 'journalier';
            } else if (viewType === 'dayGridWeek') {
                newHorizon = 'weekly';
            } else {
                newHorizon = 'monthly';
            }

            // ✅ Refetch uniquement si l'horizon a changé ET qu'on n'est pas déjà en train de refetch
            if (newHorizon !== previousHorizon && !isRefetching) {
                currentHorizon  = newHorizon;
                previousHorizon = newHorizon;
                isRefetching = true;
                setTimeout(function() {
                    calendar.refetchEvents();
                    isRefetching = false;
                }, 0);
            }
        },

        events: function(fetchInfo, successCallback, failureCallback) {
            if (!cp) {
                updateSummary({ station: '—', temperature_predite: null, horizon: '—' });
                renderRecommendedList([]);
                successCallback([]);
                return;
            }

            showLoading();

            // ✅ Horizon lu depuis la variable externe, pas recalculé ici
            const fetchHorizon = currentHorizon;
            const targetDate   = fetchInfo.startStr.substring(0, 10);

            const url = `events_agricoles.php?cp=${encodeURIComponent(cp)}`
                + `&horizon=${encodeURIComponent(fetchHorizon)}`
                + `&start=${encodeURIComponent(fetchInfo.startStr)}`
                + `&end=${encodeURIComponent(fetchInfo.endStr)}`
                + `&target_date=${encodeURIComponent(targetDate)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    hideLoading();

                    if (data.error) {
                        console.error("Erreur events_agricoles.php :", data.error);
                        updateSummary({ station: 'Erreur', temperature_predite: null, horizon: fetchHorizon });
                        renderRecommendedList([]);
                        successCallback([]);
                        return;
                    }

                    const meta = data.meta || null;
                    if (meta) {
                        updateSummary({
                            station: meta.station,
                            temperature_predite: meta.temperature_predite,
                            horizon: fetchHorizon
                        });
                        renderRecommendedList(meta.cultures_recommandees || []);
                    } else {
                        updateSummary({ station: '—', temperature_predite: null, horizon: fetchHorizon });
                        renderRecommendedList([]);
                    }

                    const rawEvents    = data.events || [];
                    const uniqueEvents = [];
                    const seenKeys     = new Set();
                    rawEvents.forEach(evt => {
                        const key = evt.title + evt.start;
                        if (!seenKeys.has(key)) {
                            seenKeys.add(key);
                            uniqueEvents.push(evt);
                        }
                    });

                    successCallback(uniqueEvents);
                })
                .catch(error => {
                    hideLoading();
                    console.error("Erreur calendrier :", error);
                    updateSummary({ station: 'Erreur', temperature_predite: null, horizon: fetchHorizon });
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