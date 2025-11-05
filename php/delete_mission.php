<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: create_mission.php?error=bad_id");
    exit();
}

$stmt = $conn->prepare("DELETE FROM missions WHERE id=?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    // Si FK ON DELETE CASCADE est posée, les assignations liées disparaissent aussi
    header("Location: create_mission.php?deleted=1");
    exit();
} else {
    header("Location: create_mission.php?error=sql");
    exit();
}
