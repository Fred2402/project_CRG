<?php
session_start();
include 'db.php';

// Vérifie si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Récupérer l'ID de l'utilisateur à supprimer
$user_id = $_GET['id'];

// Supprimer l'utilisateur de la base de données
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    header("Location: admin_dashboard.php?message=suppression_reussie");
    exit();
} else {
    echo "Erreur lors de la suppression : " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
