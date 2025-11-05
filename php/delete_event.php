<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: create_event.php?error=bad_id");
    exit();
}

$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: create_event.php?deleted=1");
        exit();
    } else {
        header("Location: create_event.php?error=sql");
        exit();
    }
} else {
    header("Location: create_event.php?error=prepare");
    exit();
}
