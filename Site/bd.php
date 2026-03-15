<?php
function getBD() {
    $host = 'localhost';
    $dbname = 'agriculture'; 
    $user = 'root'; 
    $pass = 'root'; 

    try {
        $bdd = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $bdd;
    } catch (Exception $e) {
        die('Erreur de connexion à la base : ' . $e->getMessage());
    }
}

// Initialise la variable pour qu'elle soit dispo après l'include
$bdd = getBD();