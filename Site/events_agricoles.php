<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$cp = $_GET['cp'] ?? '';
$horizon = $_GET['horizon'] ?? '';
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';
$targetDate = $_GET['target_date'] ?? '';

$cp = trim($cp);
$horizon = trim($horizon);
$start = trim($start);
$end = trim($end);
$targetDate = trim($targetDate);

if ($cp === '' || $horizon === '') {
    echo json_encode([
        "error" => "Les paramètres cp et horizon sont requis."
    ]);
    exit;
}

if ($targetDate === '') {
    $targetDate = $start !== '' ? date('Y-m-d', strtotime($start)) : date('Y-m-d');
}

$url = "http://127.0.0.1:8888/Agri/Future-Agriculteur/Site/prediction_cultures.php?cp=" . urlencode($cp)
    . "&horizon=" . urlencode($horizon)
    . "&target_date=" . urlencode($targetDate);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    echo json_encode([
        "error" => "Impossible d'appeler prediction_cultures.php (Code $httpCode)"
    ]);
    exit;
}

$data = json_decode($response, true);

if (!$data || isset($data["error"])) {
    echo json_encode([
        "error" => "Erreur dans prediction_cultures.php",
        "details" => $data
    ]);
    exit;
}

$events = [];

$tempPredite = $data["temperature_predite"] ?? null;
$station = $data["station"] ?? '';
$cultures = $data["cultures_recommandees"] ?? [];

if ($start === '') {
    $start = $data["date_prediction"] ?? date('Y-m-d');
}
if ($end === '') {
    $end = date('Y-m-d', strtotime($start . ' +1 day'));
}

$startDate = date('Y-m-d', strtotime($start));
$endDate = date('Y-m-d', strtotime($end));

if ($tempPredite !== null) {
    $events[] = [
        "title" => "🌡️ Température : " . $tempPredite . "°C",
        "start" => $startDate,
        "end" => $endDate,
        "allDay" => true,
        "color" => "#2563eb",
        "details" =>
            "Station : " . $station . "\n" .
            "Température prédite : " . $tempPredite . "°C\n" .
            "Date cible : " . ($data["target_date"] ?? $targetDate)
    ];
}

if (!empty($cultures)) {
    foreach ($cultures as $culture) {
        $emoji = ($culture["type_culture"] ?? '') === 'Fruit' ? '🍓' : '🌱';

        $events[] = [
            "title" => $emoji . " " . $culture["nom_culture"],
            "start" => $startDate,
            "end" => $endDate,
            "allDay" => true,
            "color" => "#16a34a",
            "details" =>
                "Culture : " . $culture["nom_culture"] . "\n" .
                "Type : " . $culture["type_culture"] . "\n" .
                "Saison : " . $culture["saison"] . "\n" .
                "Remarque : " . ($culture["remarque"] ?: "Aucune") . "\n" .
                "Température utilisée : " . $tempPredite . "°C"
        ];
    }
} else {
    $events[] = [
        "title" => "❌ Aucune culture recommandée",
        "start" => $startDate,
        "end" => $endDate,
        "allDay" => true,
        "color" => "#dc2626",
        "details" =>
            "Aucune culture ne correspond à la température prédite et à la saison pour cette période."
    ];
}

echo json_encode([
    "events" => $events,
    "meta" => [
        "station" => $station,
        "temperature_predite" => $tempPredite,
        "horizon" => $horizon,
        "cultures_count" => count($cultures),
        "cultures_recommandees" => $cultures
    ]
], JSON_UNESCAPED_UNICODE);
?>