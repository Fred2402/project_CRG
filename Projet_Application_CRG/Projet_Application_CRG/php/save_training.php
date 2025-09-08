<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Récupérer les données du formulaire
$user_id = $conn->real_escape_string($_POST['user_id']);
$training_name = $conn->real_escape_string($_POST['training_name']);
$niveau = $conn->real_escape_string($_POST['niveau']);
$date_completed = $conn->real_escape_string($_POST['date_completed'] ?? NULL);

// Préparer et exécuter la requête pour insérer la formation
$sql = "INSERT INTO training (user_id, training_name, niveau, date_completed) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Erreur de préparation de la requête SQL : " . $conn->error);
}

$stmt->bind_param("isss", $user_id, $training_name, $niveau, $date_completed);

if ($stmt->execute()) {
    echo "Formation ajoutée avec succès.";
    // Redirection après ajout
    header("Location: admin_dashboard.php?success=true");
} else {
    echo "Erreur lors de l'ajout de la formation : " . $stmt->error;
}

// Fermer la connexion
$stmt->close();
$conn->close();
?>
