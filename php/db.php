<?php
$servername = "localhost";
$username = "CRG_appli";
$password = "ZK1nqWvo(h[_nd1u";
$dbname = "crg_appli";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
