<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// ============================
// INPUT
// ============================
$cp = $_GET['cp'] ?? '';
$horizon = $_GET['horizon'] ?? '';
$targetDate = $_GET['target_date'] ?? '';

$cp = trim($cp);
$horizon = trim($horizon);

if ($cp === '' || $horizon === '') {
    echo json_encode(["error" => "cp et horizon sont requis"]);
    exit;
}

// ============================
// 1. STATION PROCHE
// ============================
$urlStation = "http://localhost/agri/get_station_proche.php?cp=" . urlencode($cp);
$responseStation = file_get_contents($urlStation);

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
// 2. API PYTHON
// ============================
$urlApi = "http://127.0.0.1:5000/predict?station=" . urlencode($station) . "&horizon=" . urlencode($horizon);
$responseApi = file_get_contents($urlApi);

if ($responseApi === false) {
    echo json_encode(["error" => "Impossible d'appeler l'API Python"]);
    exit;
}

$dataApi = json_decode($responseApi, true);

echo json_encode([
    "code_postal" => $cp,
    "station" => $station,
    "prediction" => $dataApi
], JSON_UNESCAPED_UNICODE);
?>
