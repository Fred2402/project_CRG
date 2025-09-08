<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];

// Récupérer les données soumises via le formulaire
$first_name = $conn->real_escape_string($_POST['first_name'] ?? '');
$last_name = $conn->real_escape_string($_POST['last_name'] ?? '');
$phone = $conn->real_escape_string($_POST['phone'] ?? '');
$date_of_birth = $conn->real_escape_string($_POST['date_of_birth'] ?? '');

// Préparer et exécuter la requête pour mettre à jour les informations de l'utilisateur
$sql = "UPDATE users SET 
        first_name = ?, 
        last_name = ?, 
        phone = ?, 
        date_of_birth = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Erreur de préparation de la requête SQL : " . $conn->error);
}

$stmt->bind_param(
    "ssssi", 
    $first_name, 
    $last_name, 
    $phone, 
    $date_of_birth, 
    $user_id
);

if ($stmt->execute()) {
    // Rediriger vers la page de profil après la mise à jour
    header("Location: profile.php?success=true");
} else {
    echo "Erreur lors de la mise à jour du profil : " . $stmt->error;
}

// Fermer la connexion
$stmt->close();
$conn->close();
?>
