<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once 'bd.php';

// ============================
// 1. PARAMÈTRES
// ============================
$cp = $_GET['cp'] ?? '';
$horizon = $_GET['horizon'] ?? '';
$targetDate = $_GET['target_date'] ?? '';

$cp = trim($cp);
$horizon = trim($horizon);
$targetDate = trim($targetDate);

if ($cp === '' || $horizon === '') {
    echo json_encode(["error" => "Les paramètres cp et horizon sont requis."]);
    exit;
}

if ($targetDate === '') {
    $targetDate = date('Y-m-d');
}

// Fonction utilitaire pour appeler une URL via cURL (plus fiable que file_get_contents)
function call_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// ============================
// 2. RÉCUPÉRER LA STATION PROCHE
// ============================
// On utilise 127.0.0.1 au lieu de localhost pour éviter les lenteurs DNS sur Mac
$urlStation = "http://127.0.0.1:8888/Agri/Future-Agriculteur/Site/get_station_proche.php?cp=" . urlencode($cp);
$responseStation = call_url($urlStation);

if ($responseStation === false) {
    echo json_encode(["error" => "Impossible d'appeler get_station_proche.php"]);
    exit;
}

$dataStation = json_decode($responseStation, true);

if (!isset($dataStation["station_proche"]["nom"])) {
    echo json_encode([
        "error" => "Station proche introuvable",
        "details" => $dataStation
    ]);
    exit;
}

$station = $dataStation["station_proche"]["nom"];

// ============================
// 3. APPELER L’API PYTHON
// ============================
$urlApi = "http://127.0.0.1:5000/predict?station=" . urlencode($station)
    . "&horizon=" . urlencode($horizon)
    . "&target_date=" . urlencode($targetDate);

$responseApi = call_url($urlApi);

if ($responseApi === false) {
    echo json_encode(["error" => "Impossible d'appeler l'API Python"]);
    exit;
}

$dataApi = json_decode($responseApi, true);

if (isset($dataApi["error"])) {
    echo json_encode([
        "error" => "Erreur renvoyée par l'API Python",
        "details" => $dataApi
    ]);
    exit;
}

// ============================
// 4. EXTRAIRE LA TEMPÉRATURE PRÉDITE
// ============================
$tempPredite = null;
if (isset($dataApi["temperature_predite"])) {
    $tempPredite = floatval($dataApi["temperature_predite"]);
} elseif (isset($dataApi[0]["temperature_predite"])) {
    $tempPredite = floatval($dataApi[0]["temperature_predite"]);
} else {
    echo json_encode([
        "error" => "Température prédite introuvable dans la réponse API",
        "api_response" => $dataApi
    ]);
    exit;
}

// ============================
// 5. DATE ET MOIS CIBLES
// ============================
$datePrediction = $dataApi["target_date"] ?? $targetDate;
$moisPred = intval(date('n', strtotime($datePrediction)));

// ============================
// 6. RÉCUPÉRER LES CULTURES (Base de données)
// ============================
try {
    $sqlCultures = "
        SELECT
            c.id_culture,
            c.nom_culture,
            c.type_culture,
            ct.t_min_germination,
            ct.t_opt_germination,
            ct.t_ideale_croissance,
            ct.t_min_croissance,
            s.mois_semis,
            s.mois_recolte,
            s.saison,
            s.remarque
        FROM culture c
        JOIN contrainte_temperature ct ON c.id_culture = ct.id_culture
        JOIN saisonnalite s ON c.id_culture = s.id_culture
    ";

    $stmtCultures = $bdd->query($sqlCultures);
    $cultures = $stmtCultures->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode(["error" => "Erreur SQL : " . $e->getMessage()]);
    exit;
}

// ============================
// 7. FILTRER LES CULTURES
// ============================
$recommandees = [];
$nonRecommandees = [];

foreach ($cultures as $culture) {
    $tempMin = floatval($culture['t_min_croissance']);
    $tempMax = floatval($culture['t_ideale_croissance']);

    // Logique de recommandation
    $tempOk = ($tempPredite >= $tempMin && $tempPredite <= $tempMax);

    $moisDebut = intval($culture['mois_semis']);
    $moisFin = intval($culture['mois_recolte']);

    if ($moisDebut <= $moisFin) {
        $moisOk = ($moisPred >= $moisDebut && $moisPred <= $moisFin);
    } else {
        $moisOk = ($moisPred >= $moisDebut || $moisPred <= $moisFin);
    }

    $cultureData = [
        "id_culture" => intval($culture["id_culture"]),
        "nom_culture" => $culture["nom_culture"],
        "type_culture" => $culture["type_culture"],
        "t_min_croissance" => $tempMin,
        "t_ideale_croissance" => $tempMax,
        "mois_semis" => $moisDebut,
        "mois_recolte" => $moisFin,
        "saison" => $culture["saison"],
        "remarque" => $culture["remarque"]
    ];

    if ($tempOk && $moisOk) {
        $recommandees[] = $cultureData;
    } else {
        $nonRecommandees[] = $cultureData;
    }
}

// ============================
// 8. RÉPONSE JSON
// ============================
echo json_encode([
    "code_postal" => $cp,
    "station" => $station,
    "horizon" => $horizon,
    "target_date" => $targetDate,
    "date_prediction" => $datePrediction,
    "mois_prediction" => $moisPred,
    "temperature_predite" => round($tempPredite, 2),
    "cultures_recommandees" => $recommandees,
    "cultures_non_recommandees" => $nonRecommandees
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>