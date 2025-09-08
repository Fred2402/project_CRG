<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $training_id = $conn->real_escape_string($_POST['training_id']);

    $sql = "UPDATE trainings SET is_validated = TRUE WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $training_id);

    if ($stmt->execute()) {
        echo "Formation validée avec succès.";
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Erreur lors de la validation de la formation: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
