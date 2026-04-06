<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once 'bd.php'; // ton fichier qui crée $bdd

// ============================
// RÉCUPÉRER LE CODE POSTAL
// ============================
$code_postal = $_GET['cp'] ?? '';
$code_postal = trim($code_postal);

if ($code_postal === '') {
    echo json_encode(["error" => "Code postal manquant"]);
    exit;
}

// force format 5 chiffres
$code_postal = str_pad($code_postal, 5, "0", STR_PAD_LEFT);

// ============================
// FONCTION DISTANCE HAVERSINE
// ============================
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

// ============================
// 1. RÉCUPÉRER LA VILLE
// ============================
$sqlVille = "
    SELECT ville_nom, ville_latitude, ville_longitude, ville_code_postal
    FROM villes
    WHERE ville_code_postal = ?
    LIMIT 1
";

$stmtVille = $bdd->prepare($sqlVille);
$stmtVille->execute([$code_postal]);
$ville = $stmtVille->fetch(PDO::FETCH_ASSOC);

if (!$ville) {
    echo json_encode([
        "error" => "Aucune ville trouvée pour ce code postal"
    ]);
    exit;
}

$latVille = floatval($ville['ville_latitude']);
$lonVille = floatval($ville['ville_longitude']);

// ============================
// 2. STATIONS AUTORISÉES
// ============================
$stationsAutorisees = [
    "MONTPELLIER-AEROPORT",
    "TOULOUSE-BLAGNAC",
    "BORDEAUX-MERIGNAC",
    "LYON-ST EXUPERY",
    "NANTES-BOUGUENAIS",
    "STRASBOURG-ENTZHEIM",
    "LILLE-LESQUIN",
    "BREST-GUIPAVAS",
    "NICE",
    "PERPIGNAN"
];

// ============================
// 3. RÉCUPÉRER LES STATIONS
// ============================
$placeholders = implode(',', array_fill(0, count($stationsAutorisees), '?'));

$sqlStations = "
    SELECT station_id, nom, lat, lon
    FROM station_meteo
    WHERE nom IN ($placeholders)
";

$stmtStations = $bdd->prepare($sqlStations);
$stmtStations->execute($stationsAutorisees);
$stations = $stmtStations->fetchAll(PDO::FETCH_ASSOC);

if (!$stations) {
    echo json_encode([
        "error" => "Aucune station autorisée trouvée dans station_meteo"
    ]);
    exit;
}

// ============================
// 4. TROUVER LA PLUS PROCHE
// ============================
$stationProche = null;
$distanceMin = INF;

foreach ($stations as $station) {
    $latStation = floatval($station['lat']);
    $lonStation = floatval($station['lon']);

    $distance = haversineDistance(
        $latVille,
        $lonVille,
        $latStation,
        $lonStation
    );

    if ($distance < $distanceMin) {
        $distanceMin = $distance;
        $stationProche = [
            "station_id" => $station['station_id'],
            "nom" => $station['nom'],
            "lat" => $latStation,
            "lon" => $lonStation,
            "distance_km" => round($distance, 2)
        ];
    }
}

// ============================
// 5. RÉPONSE JSON
// ============================
echo json_encode([
    "code_postal" => $code_postal,
    "ville" => [
        "nom" => $ville['ville_nom'],
        "lat" => $latVille,
        "lon" => $lonVille
    ],
    "station_proche" => $stationProche
], JSON_UNESCAPED_UNICODE);
?>