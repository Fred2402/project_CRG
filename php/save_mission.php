<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    if ($title === '') {
        header("Location: create_mission.php?error=title_required");
        exit();
    }

    if ($start_date === '') $start_date = null;
    if ($end_date === '') $end_date = null;

    if ($start_date === null && $end_date === null) {
        $stmt = $conn->prepare("INSERT INTO missions (title, description, start_date, end_date) VALUES (?, ?, NULL, NULL)");
        $stmt->bind_param("ss", $title, $description);
    } else {
        $stmt = $conn->prepare("INSERT INTO missions (title, description, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $description, $start_date, $end_date);
    }

    if ($stmt && $stmt->execute()) {
        header("Location: create_mission.php?created=1");
        exit();
    } else {
        header("Location: create_mission.php?error=sql");
        exit();
    }
}
