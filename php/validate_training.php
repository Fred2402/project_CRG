<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_id = (int)($_POST['training_id'] ?? 0);

    if ($training_id <= 0) {
        header("Location: admin_add_training.php?error=bad_id");
        exit();
    }

    $stmt = $conn->prepare("UPDATE training SET date_completed = CURDATE() WHERE id = ?");
    if (!$stmt) {
        header("Location: admin_add_training.php?error=prepare");
        exit();
    }
    $stmt->bind_param("i", $training_id);

    if ($stmt->execute()) {
        header("Location: admin_add_training.php?validated=1");
        exit();
    } else {
        header("Location: admin_add_training.php?error=sql");
        exit();
    }
}
header("Location: admin_add_training.php");
                    