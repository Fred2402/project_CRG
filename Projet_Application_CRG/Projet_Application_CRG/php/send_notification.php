<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $localite_id = $_POST['localite_id'];
    $message = $conn->real_escape_string($_POST['message']);
    $admin_id = $_SESSION['user_id'];
    $date = date('Y-m-d H:i:s');

    if ($localite_id == 'all') {
        // Envoyer à tous les volontaires
        $sql = "INSERT INTO notifications (message, date, user_id) SELECT '$message', '$date', id FROM users WHERE role = 'volunteer'";
    } else {
        // Envoyer aux volontaires d'une localité spécifique
        $sql = "INSERT INTO notifications (message, date, user_id) SELECT '$message', '$date', id FROM users WHERE role = 'volunteer' AND localite_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $localite_id);
    }

    if ($stmt->execute()) {
        echo "Notification envoyée avec succès.";
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Erreur lors de l'envoi de la notification: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
